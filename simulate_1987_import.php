<?php
/**
 * Teste simulando importação do contrato 1987 com os dados da planilha
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db = (new Database())->getConnection();

echo "=== SIMULAÇÃO: Importação Contrato 1987 ===\n\n";

// Criar planilha de teste EXATAMENTE como a do usuário
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers (baseados na imagem do usuário)
$sheet->setCellValue('A1', 'CONTRATO');
$sheet->setCellValue('B1', 'TP_CONTRATO');
$sheet->setCellValue('C1', 'CONTRATANTE');
$sheet->setCellValue('D1', 'CONTRATACAO');
$sheet->setCellValue('E1', 'CPF_CNPJ');  // Nome com underscore
$sheet->setCellValue('F1', 'CPF_CNPJ_PL_CONTRATANTE');  // Coluna alternativa
$sheet->setCellValue('G1', 'STATUS');
$sheet->setCellValue('H1', 'VENDA');
$sheet->setCellValue('I1', 'DATA_REGISTRO');
$sheet->setCellValue('J1', 'RECEBIMENTO');
$sheet->setCellValue('K1', 'PARCELA');
$sheet->setCellValue('L1', 'DEBITO');
$sheet->setCellValue('M1', 'EMISSAO');
$sheet->setCellValue('N1', 'VENCIMENTO');
$sheet->setCellValue('O1', 'DIAS_ATRASO');
$sheet->setCellValue('P1', 'RUA');
$sheet->setCellValue('Q1', 'NUMERO');
$sheet->setCellValue('R1', 'BAIRRO');
$sheet->setCellValue('S1', 'CEP');
$sheet->setCellValue('T1', 'CIDADE');
$sheet->setCellValue('U1', 'ESTADO');

// Dados (linha 2 - contrato 1987)
$sheet->setCellValue('A2', '1987');
$sheet->setCellValue('B2', 'PJ');
$sheet->setCellValue('C2', 'Deposito P3/31/20');
$sheet->setCellValue('D2', '');
$sheet->setCellValue('E2', '38988251300013');  // CPF sem formatação
$sheet->setCellValue('F2', '18500013000185');
$sheet->setCellValue('G2', 'R');
$sheet->setCellValue('H2', '43019241');
$sheet->setCellValue('I2', '5/03/21');
$sheet->setCellValue('J2', '15/03/21');
$sheet->setCellValue('K2', '1a parcela');
$sheet->setCellValue('L2', '381.12');
$sheet->setCellValue('M2', '16/02/21');
$sheet->setCellValue('N2', '28/02/21');
$sheet->setCellValue('O2', '');
$sheet->setCellValue('P2', '1804 BRASIL');
$sheet->setCellValue('Q2', '1475');  
$sheet->setCellValue('R2', 'CENTRO');
$sheet->setCellValue('S2', '65921000');
$sheet->setCellValue('T2', 'Graca aranha');
$sheet->setCellValue('U2', 'MA');

// Salvar
$testFile = sys_get_temp_dir() . '/teste_1987.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($testFile);

echo "1. Planilha de teste criada: $testFile\n\n";

// Verificar estado ANTES
echo "2. Estado ANTES da importação:\n";
$stmt = $db->prepare("SELECT cpf_cnpj, nome, endereco FROM pdd_perdas WHERE codigo_contrato = ?");
$stmt->execute(['1987']);
$before = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  CPF: " . ($before['cpf_cnpj'] ?: 'NULL') . "\n";
echo "  Nome: " . ($before['nome'] ?: 'NULL') . "\n";
echo "  Endereco: " . ($before['endereco'] ?: 'NULL') . "\n\n";

// Importar usando DataEnrichmentImporter
echo "3. Importando...\n";

require_once __DIR__ . '/src/Importers/DataEnrichmentImporter.php';
use App\Importers\DataEnrichmentImporter;

//Create batch
$batchStmt = $db->prepare("INSERT INTO import_batches (filename, type) VALUES (?, ?)");
$batchStmt->execute(['teste_1987.xlsx', 'enrichment']);
$batchId = $db->lastInsertId();

try {
    $importer = new DataEnrichmentImporter($db);
    $importer->import($testFile, $batchId);
    echo "  ✓ Importação concluída!\n\n";
} catch (Exception $e) {
    echo "  ✗ ERRO: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Verificar estado DEPOIS
echo "4. Estado DEPOIS da importação:\n";
$stmt->execute(['1987']);
$after = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  CPF: " . ($after['cpf_cnpj'] ?: 'NULL');
if ($after['cpf_cnpj'] !== $before['cpf_cnpj']) echo " ✓ MUDOU!";
echo "\n  Nome: " . ($after['nome'] ?: 'NULL');
if ($after['nome'] !== $before['nome']) echo " ✓ MUDOU!";
echo "\n  Endereco: " . ($after['endereco'] ?: 'NULL');
if ($after['endereco'] !== $before['endereco']) echo " ✓ MUDOU!";
echo "\n\n";

// Verificar spc_inclusos também
$stmt = $db->prepare("SELECT cpf_cnpj, contratante, rua, numero, bairro FROM spc_inclusos WHERE contrato = ?");
$stmt->execute(['1987']);
$spc = $stmt->fetch(PDO::FETCH_ASSOC);
if ($spc) {
    echo "5. spc_inclusos:\n";
    echo "  CPF: " . ($spc['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "  Contratante: " . ($spc['contratante'] ?: 'NULL') . "\n";
    echo "  Rua: " . ($spc['rua'] ?: 'NULL') . "\n";
    echo "  Numero: " . ($spc['numero'] ?: 'NULL') . "\n";
    echo "  Bairro: " . ($spc['bairro'] ?: 'NULL') . "\n";
}

// Cleanup
unlink($testFile);
echo "\n✓ Teste concluído!\n";
