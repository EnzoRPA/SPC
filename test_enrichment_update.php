<?php
/**
 * Test script to verify that DataEnrichmentImporter now updates both tables
 * This creates test data and verifies the update behavior
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== DataEnrichmentImporter Verification Test ===\n\n";

// Test 1: Check if test data exists
echo "Test 1: Checking for existing test records...\n";

$stmtPdd = $db->prepare("SELECT COUNT(*) FROM pdd_perdas WHERE codigo_contrato_norm = ?");
$stmtSpc = $db->prepare("SELECT COUNT(*) FROM spc_inclusos WHERE contrato_norm = ?");

$testContrato = 'TEST12345';
$stmtPdd->execute([$testContrato]);
$pddCount = $stmtPdd->fetchColumn();

$stmtSpc->execute([$testContrato]);
$spcCount = $stmtSpc->fetchColumn();

echo "  - Records in pdd_perdas with contrato '$testContrato': $pddCount\n";
echo "  - Records in spc_inclusos with contrato '$testContrato': $spcCount\n\n";

// Test 2: Create test records if they don't exist
if ($pddCount == 0) {
    echo "Test 2: Creating test record in pdd_perdas...\n";
    $db->exec("
        INSERT INTO pdd_perdas (batch_id, codigo_contrato, data_vencimento, codigo_contrato_norm, nome, cpf_cnpj, endereco, valor, codigo_venda)
        VALUES (1, 'TEST-12345', '2024-01-01', '$testContrato', NULL, NULL, NULL, 1000.00, 'VENDA001')
    ");
    echo "  ✓ Test record created in pdd_perdas\n\n";
}

if ($spcCount == 0) {
    echo "Test 3: Creating test record in spc_inclusos...\n";
    $db->exec("
        INSERT INTO spc_inclusos (batch_id, contrato, tp_contrato, contratante, cpf_cnpj, debito, vencimento, contrato_norm, cpf_cnpj_norm, data_inclusao)
        VALUES (1, 'TEST-12345', 'PDD', NULL, NULL, 1000.00, '2024-01-01', '$testContrato', NULL, '2024-01-01')
    ");
    echo "  ✓ Test record created in spc_inclusos\n\n";
}

// Test 3: Create a test Excel file with enrichment data
echo "Test 4: Creating test Excel file with enrichment data...\n";

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Contrato');
$sheet->setCellValue('B1', 'Nome');
$sheet->setCellValue('C1', 'CPF');
$sheet->setCellValue('D1', 'Endereco');

// Test data
$sheet->setCellValue('A2', 'TEST-12345');
$sheet->setCellValue('B2', 'João da Silva Teste');
$sheet->setCellValue('C2', '123.456.789-00');
$sheet->setCellValue('D2', 'Rua Teste, 123 - Bairro Teste - Cidade Teste/SP - CEP: 12345-678');

$testFile = sys_get_temp_dir() . '/test_enrichment_' . time() . '.xlsx';
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save($testFile);

echo "  ✓ Test file created: $testFile\n\n";

// Test 4: Check data BEFORE import
echo "Test 5: Checking data BEFORE import...\n";
$stmtPdd = $db->prepare("SELECT nome, cpf_cnpj, endereco FROM pdd_perdas WHERE codigo_contrato_norm = ?");
$stmtSpc = $db->prepare("SELECT contratante, cpf_cnpj FROM spc_inclusos WHERE contrato_norm = ?");

$stmtPdd->execute([$testContrato]);
$beforePdd = $stmtPdd->fetch(PDO::FETCH_ASSOC);

$stmtSpc->execute([$testContrato]);
$beforeSpc = $stmtSpc->fetch(PDO::FETCH_ASSOC);

echo "  PDD_PERDAS Before:\n";
echo "    - Nome: " . ($beforePdd['nome'] ?? 'NULL') . "\n";
echo "    - CPF: " . ($beforePdd['cpf_cnpj'] ?? 'NULL') . "\n";
echo "    - Endereco: " . ($beforePdd['endereco'] ?? 'NULL') . "\n";

echo "  SPC_INCLUSOS Before:\n";
echo "    - Contratante: " . ($beforeSpc['contratante'] ?? 'NULL') . "\n";
echo "    - CPF: " . ($beforeSpc['cpf_cnpj'] ?? 'NULL') . "\n\n";

// Test 5: Run the importer
echo "Test 6: Running DataEnrichmentImporter...\n";

use App\Importer;

$importer = new Importer($db);

try {
    $batchId = $importer->importarArquivo($testFile, 'enrichment');
    echo "  ✓ Import completed successfully! Batch ID: $batchId\n\n";
} catch (Exception $e) {
    echo "  ✗ Import failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 6: Check data AFTER import
echo "Test 7: Checking data AFTER import...\n";
$stmtPdd->execute([$testContrato]);
$afterPdd = $stmtPdd->fetch(PDO::FETCH_ASSOC);

$stmtSpc->execute([$testContrato]);
$afterSpc = $stmtSpc->fetch(PDO::FETCH_ASSOC);

echo "  PDD_PERDAS After:\n";
echo "    - Nome: " . ($afterPdd['nome'] ?? 'NULL') . "\n";
echo "    - CPF: " . ($afterPdd['cpf_cnpj'] ?? 'NULL') . "\n";
echo "    - Endereco: " . ($afterPdd['endereco'] ?? 'NULL') . "\n";

echo "  SPC_INCLUSOS After:\n";
echo "    - Contratante: " . ($afterSpc['contratante'] ?? 'NULL') . "\n";
echo "    - CPF: " . ($afterSpc['cpf_cnpj'] ?? 'NULL') . "\n\n";

// Test 7: Verify updates
echo "Test 8: Verifying updates...\n";
$pddUpdated = ($afterPdd['nome'] !== $beforePdd['nome']) || 
              ($afterPdd['cpf_cnpj'] !== $beforePdd['cpf_cnpj']) || 
              ($afterPdd['endereco'] !== $beforePdd['endereco']);

$spcUpdated = ($afterSpc['contratante'] !== $beforeSpc['contratante']) || 
              ($afterSpc['cpf_cnpj'] !== $beforeSpc['cpf_cnpj']);

if ($pddUpdated) {
    echo "  ✓ PDD_PERDAS was updated successfully!\n";
} else {
    echo "  ✗ PDD_PERDAS was NOT updated\n";
}

if ($spcUpdated) {
    echo "  ✓ SPC_INCLUSOS was updated successfully!\n";
} else {
    echo "  ✗ SPC_INCLUSOS was NOT updated\n";
}

// Cleanup
echo "\nTest 9: Cleaning up test data...\n";
$db->exec("DELETE FROM pdd_perdas WHERE codigo_contrato_norm = '$testContrato'");
$db->exec("DELETE FROM spc_inclusos WHERE contrato_norm = '$testContrato'");
unlink($testFile);
echo "  ✓ Test data cleaned up\n\n";

// Final result
echo "=== TEST RESULTS ===\n";
if ($pddUpdated && $spcUpdated) {
    echo "✓ ALL TESTS PASSED! Both tables are being updated correctly.\n";
} else {
    echo "✗ SOME TESTS FAILED. Please review the output above.\n";
    exit(1);
}
