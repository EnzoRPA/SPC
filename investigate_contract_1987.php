<?php
/**
 * Investigação específica do contrato 1987
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

$database = new Database();
$db = $database->getConnection();

echo "=== INVESTIGAÇÃO: Contrato 1987 ===\n\n";

$contrato = '1987';
$contratoNorm = Normalizer::normalizarContrato($contrato);

echo "Contrato original: $contrato\n";
echo "Contrato normalizado: $contratoNorm\n\n";

// 1. Verificar em pdd_perdas
echo "1. Verificando em pdd_perdas:\n";
$stmt = $db->prepare("SELECT id, codigo_contrato, codigo_contrato_norm, nome, cpf_cnpj, endereco, batch_id 
                      FROM pdd_perdas 
                      WHERE codigo_contrato = ? OR codigo_contrato_norm = ?");
$stmt->execute([$contrato, $contratoNorm]);
$pddResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($pddResults) > 0) {
    echo "  ✓ Encontrado " . count($pddResults) . " registro(s):\n";
    foreach ($pddResults as $r) {
        echo "    ID: {$r['id']}\n";
        echo "      codigo_contrato: {$r['codigo_contrato']}\n";
        echo "      codigo_contrato_norm: {$r['codigo_contrato_norm']}\n";
        echo "      Nome: " . ($r['nome'] ?: 'NULL') . "\n";
        echo "      CPF: " . ($r['cpf_cnpj'] ?: 'NULL') . "\n";
        echo "      Endereco: " . ($r['endereco'] ?: 'NULL') . "\n";
        echo "      Batch ID: " . ($r['batch_id'] ?: 'NULL') . "\n\n";
    }
} else {
    echo "  ✗ NÃO ENCONTRADO em pdd_perdas\n\n";
}

// 2. Verificar em spc_inclusos
echo "2. Verificando em spc_inclusos:\n";
$stmt = $db->prepare("SELECT id, contrato, contrato_norm, contratante, cpf_cnpj, rua, numero, bairro, cidade, estado, cep, batch_id 
                      FROM spc_inclusos 
                      WHERE contrato = ? OR contrato_norm = ?");
$stmt->execute([$contrato, $contratoNorm]);
$spcResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($spcResults) > 0) {
    echo "  ✓ Encontrado " . count($spcResults) . " registro(s):\n";
    foreach ($spcResults as $r) {
        echo "    ID: {$r['id']}\n";
        echo "      contrato: {$r['contrato']}\n";
        echo "      contrato_norm: {$r['contrato_norm']}\n";
        echo "      Contratante: " . ($r['contratante'] ?: 'NULL') . "\n";
        echo "      CPF: " . ($r['cpf_cnpj'] ?: 'NULL') . "\n";
        echo "      Rua: " . ($r['rua'] ?: 'NULL') . "\n";
        echo "      Numero: " . ($r['numero'] ?: 'NULL') . "\n";
        echo "      Bairro: " . ($r['bairro'] ?: 'NULL') . "\n";
        echo "      Cidade: " . ($r['cidade'] ?: 'NULL') . "\n";
        echo "      Estado: " . ($r['estado'] ?: 'NULL') . "\n";
        echo "      CEP: " . ($r['cep'] ?: 'NULL') . "\n";
        echo "      Batch ID: " . ($r['batch_id'] ?: 'NULL') . "\n\n";
    }
} else {
    echo "  ✗ NÃO ENCONTRADO em spc_inclusos\n\n";
}

// 3. Verificar últimas importações enrichment
echo "3. Últimas importações de tipo 'enrichment':\n";
$stmt = $db->query("SELECT id, filename, imported_at FROM import_batches WHERE type = 'enrichment' ORDER BY id DESC LIMIT 5");
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($batches) > 0) {
    foreach ($batches as $b) {
        echo "  - Batch {$b['id']}: {$b['filename']} em {$b['imported_at']}\n";
    }
} else {
    echo "  ⚠ Nenhuma importação de tipo 'enrichment' encontrada!\n";
}

echo "\n4. Diagnóstico:\n";

if (count($pddResults) === 0 && count($spcResults) === 0) {
    echo "  ⚠ PROBLEMA: Contrato 1987 NÃO EXISTE no banco de dados!\n";
    echo "    Você precisa primeiro importar este contrato via:\n";
    echo "    - Item 3: PDD Perdas (para pdd_perdas)\n";
    echo "    - Item 1: SPC Inclusos (para spc_inclusos)\n";
    echo "    DEPOIS você pode atualizar os dados via Item 6.\n";
} elseif (count($pddResults) > 0 && ($pddResults[0]['cpf_cnpj'] === null || $pddResults[0]['cpf_cnpj'] === '')) {
    echo "  ⚠ PROBLEMA: Contrato existe mas CPF está NULL/vazio!\n";
    echo "    A importação via Item 6 não funcionou.\n";
    echo "    Possíveis causas:\n";
    echo "    1. A coluna 'CPF' não foi detectada na planilha\n";
    echo "    2. O valor do CPF estava vazio na planilha\n";
    echo "    3. O contrato normalizado não deu match ({$pddResults[0]['codigo_contrato_norm']} vs $contratoNorm)\n";
} else {
    echo "  ℹ Contrato existe e tem dados.\n";
    if (count($pddResults) > 0 && $pddResults[0]['cpf_cnpj']) {
        echo "  ✓ CPF encontrado em pdd_perdas: {$pddResults[0]['cpf_cnpj']}\n";
    }
    if (count($spcResults) > 0 && $spcResults[0]['cpf_cnpj']) {
        echo "  ✓ CPF encontrado em spc_inclusos: {$spcResults[0]['cpf_cnpj']}\n";
    }
}

echo "\n";
