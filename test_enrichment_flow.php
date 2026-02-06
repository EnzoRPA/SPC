<?php
require 'vendor/autoload.php';
require 'config/db.php';
require 'src/Importers/DataEnrichmentImporter.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Importers\DataEnrichmentImporter;

$db = (new Database())->getConnection();

echo "=== TESTING DATA ENRICHMENT FLOW ===\n";

// 1. Setup Dummy Record with MISSING data
$contrato = "TEST-ENRICH-999";
$db->exec("DELETE FROM pdd_perdas WHERE codigo_contrato = '$contrato'");
$db->prepare("INSERT INTO pdd_perdas (codigo_contrato, codigo_contrato_norm, valor, data_vencimento) VALUES (?, ?, 100.00, '2025-01-01')")->execute([$contrato, $contrato]);

echo "Created dummy record: $contrato (Empty Name/CPF)\n";

// 2. Create Excel File
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'CONTRATO');
$sheet->setCellValue('B1', 'NOME');
$sheet->setCellValue('C1', 'CPF');
$sheet->setCellValue('D1', 'ENDERECO');

$sheet->setCellValue('A2', $contrato);
$sheet->setCellValue('B2', 'Fulano Enriched'); // NEW NAME
$sheet->setCellValue('C2', '123.456.789-00'); // NEW CPF
$sheet->setCellValue('D2', 'Rua Teste, 100'); // NEW ADDRESS

$sheet->setCellValue('A3', 'NON-EXISTENT-CONTRACT'); // Should be ignored
$sheet->setCellValue('B3', 'Ghost User');

$fileName = 'test_enrich_upl.xlsx';
$writer = new Xlsx($spreadsheet);
$filePath = __DIR__ . '/' . $fileName;
$writer->save($filePath);

echo "Created test file: $filePath\n";

if (!file_exists($filePath)) {
    die("File creation failed.\n");
}

// 3. Run Importer
echo "Running Importer...\n";
$importer = new DataEnrichmentImporter($db);
$importer->import($filePath, 999);

// 4. Verify Result
$stmt = $db->prepare("SELECT * FROM pdd_perdas WHERE codigo_contrato = ?");
$stmt->execute([$contrato]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n--- Result for $contrato ---\n";
print_r($row);

if ($row['nome'] === 'Fulano Enriched' && $row['cpf_cnpj'] === '12345678900') {
    echo "\nSUCCESS: Record updated!\n";
} else {
    echo "\nFAILURE: Record not updated correctly.\n";
}

// 5. Verify Isolation
$stmt = $db->prepare("SELECT COUNT(*) FROM pdd_perdas WHERE codigo_contrato = 'NON-EXISTENT-CONTRACT'");
$stmt->execute();
$count = $stmt->fetchColumn();
echo "Non-existent records created: $count (Should be 0)\n";

// Cleanup
$db->exec("DELETE FROM pdd_perdas WHERE codigo_contrato = '$contrato'");
unlink($fileName);
