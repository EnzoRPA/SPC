<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== VERIFICAÇÃO: Estado atual do contrato 1987 ===\n\n";

// Verificar pdd_perdas
$stmt = $db->prepare("SELECT cpf_cnpj, nome, endereco FROM pdd_perdas WHERE codigo_contrato = ?");
$stmt->execute(['1987']);
$pdd = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pdd) {
    echo "pdd_perdas:\n";
    echo "  CPF: " . ($pdd['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "  Nome: " . ($pdd['nome'] ?: 'NULL') . "\n";
    echo "  Endereco: " . ($pdd['endereco'] ?: 'NULL') . "\n\n";
} else {
    echo "⚠ Contrato 1987 NÃO encontrado em pdd_perdas!\n\n";
}

// Verificar spc_inclusos
$stmt = $db->prepare("SELECT cpf_cnpj, contratante, rua, numero FROM spc_inclusos WHERE contrato = ?");
$stmt->execute(['1987']);
$spc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($spc) {
    echo "spc_inclusos:\n";
    echo "  CPF: " . ($spc['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "  Contratante: " . ($spc['contratante'] ?: 'NULL') . "\n";
    echo "  Rua: " . ($spc['rua'] ?: 'NULL') . "\n";
    echo "  Numero: " . ($spc['numero'] ?: 'NULL') . "\n\n";
} else {
    echo "⚠ Contrato 1987 NÃO encontrado em spc_inclusos!\n\n";
}

echo "Agora REIMPORTE a planilha e execute este script novamente.\n";
