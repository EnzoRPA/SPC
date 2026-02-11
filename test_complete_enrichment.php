<?php
/**
 * Comprehensive test to verify DataEnrichmentImporter updates
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Importers/DataEnrichmentImporter.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Importers\DataEnrichmentImporter;

echo "=== TESTE COMPLETO: DataEnrichmentImporter ===\n\n";

// 1. Verificar se o código foi atualizado
echo "1. Verificando código fonte...\n";
$code = file_get_contents(__DIR__ . '/src/Importers/DataEnrichmentImporter.php');

$checks = [
    'Memory optimization' => strpos($code, "ini_set('memory_limit', '1024M')") !== false,
    'Empty cells skip' => strpos($code, 'setReadEmptyCells(false)') !== false,
    'SPC endereco update' => strpos($code, 'rua = COALESCE(?, rua)') !== false,
    'Flexible column detection' => strpos($code, 'str_replace') !== false && strpos($code, 'colNameClean') !== false,
    'Debug logging' => strpos($code, 'Columns detected:') !== false,
    'Address component extraction' => strpos($code, '$rua = ($idxRua !== -1 && isset($rowData[$idxRua]))') !== false,
];

$allPassed = true;
foreach ($checks as $feature => $passed) {
    echo "  " . ($passed ? "✓" : "✗") . " $feature\n";
    if (!$passed) $allPassed = false;
}

if (!$allPassed) {
    echo "\n⚠ ERRO: Nem todas as alterações foram aplicadas!\n";
    echo "  O arquivo pode estar em cache ou não foi salvo corretamente.\n";
    exit(1);
}

echo "  ✓ TODAS as alterações estão presentes no código!\n\n";

// 2. Criar planilha de teste
echo "2. Criando planilha de teste...\n";
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'CONTRATO');
$sheet->setCellValue('B1', 'NOME');
$sheet->setCellValue('C1', 'CPF');
$sheet->setCellValue('D1', 'RUA');
$sheet->setCellValue('E1', 'NUMERO');
$sheet->setCellValue('F1', 'BAIRRO');
$sheet->setCellValue('G1', 'CIDADE');
$sheet->setCellValue('H1', 'ESTADO');
$sheet->setCellValue('I1', 'CEP');

// Test data - Get a real contract from database
$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT codigo_contrato, codigo_contrato_norm FROM pdd_perdas LIMIT 1");
$testContract = $stmt->fetch(PDO::FETCH_ASSOC);

if ($testContract) {
    $sheet->setCellValue('A2', $testContract['codigo_contrato']);
    $sheet->setCellValue('B2', 'João da Silva Teste');
    $sheet->setCellValue('C2', '12345678900');
    $sheet->setCellValue('D2', 'Rua das Flores');
    $sheet->setCellValue('E2', '123');
    $sheet->setCellValue('F2', 'Centro');
    $sheet->setCellValue('G2', 'São Paulo');
    $sheet->setCellValue('H2', 'SP');
    $sheet->setCellValue('I2', '01000-000');
    
    echo "  ✓ Planilha criada com contrato: {$testContract['codigo_contrato']}\n\n";
} else {
    echo "  ✗ Nenhum contrato encontrado na base para teste\n";
    exit(1);
}

// Save test file
$testFile = sys_get_temp_dir() . '/test_enrichment.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($testFile);
echo "  ✓ Arquivo salvo em: $testFile\n\n";

// 3. Verificar dados ANTES da importação
echo "3. Dados ANTES da importação:\n";
$checkStmt = $db->prepare("SELECT nome, cpf_cnpj, endereco FROM pdd_perdas WHERE codigo_contrato_norm = ?");
$checkStmt->execute([$testContract['codigo_contrato_norm']]);
$beforePdd = $checkStmt->fetch(PDO::FETCH_ASSOC);
echo "  pdd_perdas:\n";
echo "    Nome: " . ($beforePdd['nome'] ?: 'NULL') . "\n";
echo "    CPF: " . ($beforePdd['cpf_cnpj'] ?: 'NULL') . "\n";
echo "    Endereco: " . ($beforePdd['endereco'] ?: 'NULL') . "\n";

$checkStmt = $db->prepare("SELECT contratante, cpf_cnpj, rua, numero, bairro, cidade, estado, cep FROM spc_inclusos WHERE contrato_norm = ?");
$checkStmt->execute([$testContract['codigo_contrato_norm']]);
$beforeSpc = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($beforeSpc) {
    echo "  spc_inclusos:\n";
    echo "    Contratante: " . ($beforeSpc['contratante'] ?: 'NULL') . "\n";
    echo "    CPF: " . ($beforeSpc['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "    Rua: " . ($beforeSpc['rua'] ?: 'NULL') . "\n";
    echo "    Numero: " . ($beforeSpc['numero'] ?: 'NULL') . "\n";
    echo "    Bairro: " . ($beforeSpc['bairro'] ?: 'NULL') . "\n";
    echo "    Cidade: " . ($beforeSpc['cidade'] ?: 'NULL') . "\n";
    echo "    Estado: " . ($beforeSpc['estado'] ?: 'NULL') . "\n";
    echo "    CEP: " . ($beforeSpc['cep'] ?: 'NULL') . "\n";
} else {
    echo "  spc_inclusos: Registro não encontrado\n";
}

echo "\n4. Executando importação...\n";

// Create batch
$batchStmt = $db->prepare("INSERT INTO import_batches (filename, type) VALUES (?, ?)");
$batchStmt->execute(['test_enrichment.xlsx', 'enrichment']);
$batchId = $db->lastInsertId();
echo "  ✓ Batch criado: ID $batchId\n";

// Run importer
try {
    $importer = new DataEnrichmentImporter($db);
    
    // Capture error_log output
    ob_start();
    $importer->import($testFile, $batchId);
    $output = ob_get_clean();
    
    echo "  ✓ Importação executada com sucesso!\n\n";
} catch (Exception $e) {
    echo "  ✗ ERRO na importação: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Verificar dados DEPOIS da importação
echo "5. Dados DEPOIS da importação:\n";
$checkStmt->execute([$testContract['codigo_contrato_norm']]);
$afterPdd = $checkStmt->fetch(PDO::FETCH_ASSOC);
echo "  pdd_perdas:\n";
echo "    Nome: " . ($afterPdd['nome'] ?: 'NULL') . " " . ($afterPdd['nome'] !== $beforePdd['nome'] ? "✓ MUDOU" : "= igual") . "\n";
echo "    CPF: " . ($afterPdd['cpf_cnpj'] ?: 'NULL') . " " . ($afterPdd['cpf_cnpj'] !== $beforePdd['cpf_cnpj'] ? "✓ MUDOU" : "= igual") . "\n";
echo "    Endereco: " . ($afterPdd['endereco'] ?: 'NULL') . " " . ($afterPdd['endereco'] !== $beforePdd['endereco'] ? "✓ MUDOU" : "= igual") . "\n";

$checkStmt = $db->prepare("SELECT contratante, cpf_cnpj, rua, numero, bairro, cidade, estado, cep FROM spc_inclusos WHERE contrato_norm = ?");
$checkStmt->execute([$testContract['codigo_contrato_norm']]);
$afterSpc = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($afterSpc) {
    echo "  spc_inclusos:\n";
    echo "    Contratante: " . ($afterSpc['contratante'] ?: 'NULL') . " " . ($afterSpc['contratante'] !== ($beforeSpc['contratante'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
    echo "    CPF: " . ($afterSpc['cpf_cnpj'] ?: 'NULL') . " " . ($afterSpc['cpf_cnpj'] !== ($beforeSpc['cpf_cnpj'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
    echo "    Rua: " . ($afterSpc['rua'] ?: 'NULL') . " " . ($afterSpc['rua'] !== ($beforeSpc['rua'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
    echo "    Numero: " . ($afterSpc['numero'] ?: 'NULL') . " " . ($afterSpc['numero'] !== ($beforeSpc['numero'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
    echo "    Bairro: " . ($afterSpc['bairro'] ?: 'NULL') . " " . ($afterSpc['bairro'] !== ($beforeSpc['bairro'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
    echo "    Cidade: " . ($afterSpc['cidade'] ?: 'NULL') . " " . ($afterSpc['cidade'] !== ($beforeSpc['cidade'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
    echo "    Estado: " . ($afterSpc['estado'] ?: 'NULL') . " " . ($afterSpc['estado'] !== ($beforeSpc['estado'] ?? null) ? "✓ MUDOU" : "= igual") . "\n  ";
    echo "    CEP: " . ($afterSpc['cep'] ?: 'NULL') . " " . ($afterSpc['cep'] !== ($beforeSpc['cep'] ?? null) ? "✓ MUDOU" : "= igual") . "\n";
}

// 6. Verificar logs (se disponíveis)
echo "\n6. Verificando logs do PHP...\n";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = substr($logs, -2000); // Últimos 2000 caracteres
    
    if (strpos($recentLogs, 'DataEnrichmentImporter - Columns detected:') !== false) {
        echo "  ✓ Logs de detecção de colunas encontrados!\n";
        // Extract and show column detection
        preg_match_all('/(\w+): (Col \d+|NOT FOUND)/', $recentLogs, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            echo "    {$match[1]}: {$match[2]}\n";
        }
    } else {
        echo "  ⚠ Logs de detecção de colunas não encontrados nos logs recentes\n";
    }
} else {
    echo "  ℹ Arquivo de log não disponível: $logFile\n";
}

echo "\n=== RESULTADO DO TESTE ===\n";
if ($afterSpc && $afterSpc['rua'] === 'Rua das Flores' && $afterSpc['numero'] === '123') {
    echo "✅ SUCESSO TOTAL! Endereço foi atualizado corretamente em spc_inclusos!\n";
} else {
    echo "⚠ ATENÇÃO: Endereço pode não ter sido atualizado corretamente.\n";
    echo "  Verifique se o contrato existe em spc_inclusos.\n";
}

// Cleanup
unlink($testFile);
echo "\n✓ Arquivo de teste removido.\n";
