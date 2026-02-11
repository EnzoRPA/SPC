<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== PRÉ-TESTE: Criar dados para contrato 1987 ===\n\n";

// Verificar se contrato 1987 existe em pdd_perdas
$stmt = $db->prepare("SELECT id FROM pdd_perdas WHERE codigo_contrato = '1987'");
$stmt->execute();
$existsPdd = $stmt->fetch();

if (!$existsPdd) {
    echo "⚠ Contrato 1987 NÃO existe em pdd_perdas. Criando...\n";
    $stmt = $db->prepare("INSERT INTO pdd_perdas (codigo_contrato, codigo_contrato_norm, vencimento, valor) VALUES ('1987', '1987', '2022-01-01', 100.00)");
    $stmt->execute();
    echo "  ✓ Criado com ID: " . $db->lastInsertId() . "\n\n";
} else {
    echo "✓ Contrato 1987 JÁ existe em pdd_perdas (ID: {$existsPdd['id']})\n\n";
}

// Verificar se existe em spc_inclusos
$stmt = $db->prepare("SELECT id FROM spc_inclusos WHERE contrato = '1987'");
$stmt->execute();
$existsSpc = $stmt->fetch();

if (!$existsSpc) {
    echo "⚠ Contrato 1987 NÃO existe em spc_inclusos. Criando...\n";
    $stmt = $db->prepare("INSERT INTO spc_inclusos (contrato, contrato_norm, vencimento, valor) VALUES ('1987', '1987', '2022-01-01', 100.00)");
    $stmt->execute();
    echo "  ✓ Criado com ID: " . $db->lastInsertId() . "\n\n";
} else {
    echo "✓ Contrato 1987 JÁ existe em spc_inclusos (ID: {$existsSpc['id']})\n\n";
}

echo "=== ESTADO ATUAL ===\n\n";
$stmt = $db->query("SELECT cpf_cnpj, nome FROM pdd_perdas WHERE codigo_contrato = '1987'");
$pdd = $stmt->fetch(PDO::FETCH_ASSOC);
echo "pdd_perdas:\n";
echo "  CPF: " . ($pdd['cpf_cnpj'] ?: 'NULL') . "\n";
echo "  Nome: " . ($pdd['nome'] ?: 'NULL') . "\n\n";

$stmt = $db->query("SELECT cpf_cnpj, contratante FROM spc_inclusos WHERE contrato = '1987'");
$spc = $stmt->fetch(PDO::FETCH_ASSOC);
echo "spc_inclusos:\n";
echo "  CPF: " . ($spc['cpf_cnpj'] ?: 'NULL') . "\n";
echo "  Contratante: " . ($spc['contratante'] ?: 'NULL') . "\n\n";

echo "✓ Pronto! Agora importe sua planilha teste via item 6\n";
echo "  e depois execute: php check_1987.php\n";
