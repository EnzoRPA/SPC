<?php
/**
 * TESTE DIRETO com a planilha EXATA do usuário
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Importers/DataEnrichmentImporter.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Importers\DataEnrichmentImporter;

echo "=== TESTE DIRETO: Planilha do Usuário ===\n\n";

$db = (new Database())->getConnection();

// 1. Criar planilha EXATAMENTE como o usuário mostrou
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'CONTRATO');
$sheet->setCellValue('B1', 'NOME');
$sheet->setCellValue('C1', 'CPF');

$sheet->setCellValue('A2', '1987');
$sheet->setCellValue('B2', 'JOAO SILVA');
$sheet->setCellValue('C2', '12345678900');

$testFile = sys_get_temp_dir() . '/teste_direto.xlsx';
$writer = new Xlsx($spreadsheet);

try {
    $writer->save($testFile);
    echo "✓ Planilha criada: $testFile\n\n";
} catch (Exception $e) {
    echo "✗ ERRO ao criar planilha: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Estado ANTES
echo "Estado ANTES:\n";
$stmt = $db->prepare("SELECT cpf_cnpj, nome FROM pdd_perdas WHERE codigo_contrato = ?");
$stmt->execute(['1987']);
$before = $stmt->fetch(PDO::FETCH_ASSOC);
if ($before) {
    echo "  pdd_perdas encontrado:\n";
    echo "    CPF: " . ($before['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "    Nome: " . ($before['nome'] ?: 'NULL') . "\n\n";
} else {
    echo "  ✗ Contrato 1987 NÃO EXISTE em pdd_perdas!\n";
    echo "  Criando registro básico...\n";
    $stmt = $db->prepare("INSERT INTO pdd_perdas (codigo_contrato, codigo_contrato_norm, vencimento, valor) VALUES (?, ?, ?, ?)");
    $stmt->execute(['1987', '1987', '2022-01-01', 100]);
    echo "  ✓ Criado!\n\n";
    $before = ['cpf_cnpj' => null, 'nome' => null];
}

// 3. Importar
echo "Importando...\n";
$batchStmt = $db->prepare("INSERT INTO import_batches (filename, type) VALUES (?, ?)");
$batchStmt->execute(['teste_direto.xlsx', 'enrichment']);
$batchId = $db->lastInsertId();

try {
    $importer = new DataEnrichmentImporter($db);
    
    // Capturar TODOS os error_log
    set_error_handler(function($errno, $errstr) {
        echo "[LOG] $errstr\n";
    });
    
    $importer->import($testFile, $batchId);
    
    restore_error_handler();
    
    echo "✓ Importação concluída!\n\n";
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// 4. Estado DEPOIS
echo "Estado DEPOIS:\n";
$stmt->execute(['1987']);
$after = $stmt->fetch(PDO::FETCH_ASSOC);

if ($after) {
    echo "  pdd_perdas:\n";
    echo "    CPF: " . ($after['cpf_cnpj'] ?: 'NULL');
    if ($after['cpf_cnpj'] !== $before['cpf_cnpj']) echo " ← MUDOU!";
    echo "\n";
    echo "    Nome: " . ($after['nome'] ?: 'NULL');
    if ($after['nome'] !== $before['nome']) echo " ← MUDOU!";
    echo "\n\n";
    
    if ($after['cpf_cnpj'] === '12345678900') {
        echo "✅ SUCESSO! CPF foi atualizado corretamente!\n";
    } else {
        echo "❌ FALHOU! CPF não foi atualizado.\n";
    }
} else {
    echo "  ✗ Registro não encontrado!\n";
}

unlink($testFile);
