<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Importers\PddPerdasImporter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PDO;

// 1. Setup InMemory DB
$db = new PDO('sqlite::memory:');
$db->exec("CREATE TABLE pdd_perdas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INT,
    codigo_venda TEXT,
    codigo_contrato TEXT,
    data_vencimento DATE,
    codigo_contrato_norm TEXT
)");

// 2. Create Dummy Excel File
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers consistent with User Image: "Código da Venda", "Número da Parcela", "Data de Vencimento", "Valor", ... "Código do Contrato"
// We only care about matching "Vencimento" and "Contrato"
$sheet->setCellValue('A1', 'Código da Venda');
$sheet->setCellValue('B1', 'Data de Vencimento');
$sheet->setCellValue('C1', 'Código do Contrato');

// Row 1 Data
// Vencimento: Excel Date for 2025-04-20. 
// 2025-04-20 is 45767 in Excel serial date format (approx). 
// PHPSpreadsheet allows setting value as date.
$dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel('2025-04-20');
$sheet->setCellValue('A2', 'VENDA-123');
$sheet->setCellValue('B2', $dateValue); // Raw numeric value
$sheet->getStyle('B2')->getNumberFormat()->setFormatCode('mm/dd/yyyy'); // Display as 04/20/2025
$sheet->setCellValue('C2', 'CONTRATO-999');

$tempFile = __DIR__ . '/temp_test_pdd.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($tempFile);

// 3. Run Importer
echo "Running Importer on $tempFile...\n";
$importer = new PddPerdasImporter($db);

try {
    $importer->import($tempFile, 1);
    echo "Import execution finished.\n";
} catch (Exception $e) {
    echo "Import Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Verify DB content
$stmt = $db->query("SELECT * FROM pdd_perdas");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "DB Results:\n";
print_r($rows);

// Cleanup
// unlink($tempFile);

// Assertions
if (count($rows) === 1 && $rows[0]['data_vencimento'] === '2025-04-20' && $rows[0]['codigo_contrato'] === 'CONTRATO-999') {
    echo "SUCCESS: Record imported correctly with correct date.\n";
} else {
    echo "FAILURE: Record not found or incorrect data.\n";
}
