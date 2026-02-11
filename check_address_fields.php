<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== VERIFICAÇÃO: Campos de Endereço no contrato 1987 ===\n\n";

// Verificar pdd_perdas
$stmt = $db->prepare("SELECT 
    contrato, 
    cpf_cnpj, 
    nome, 
    rua,
    numero,
    bairro,
    cep,
    cidade,
    estado,
    endereco
FROM pdd_perdas WHERE codigo_contrato = ?");
$stmt->execute(['1987']);
$pdd = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pdd) {
    echo "📋 pdd_perdas:\n";
    echo "  Contrato: " . ($pdd['contrato'] ?: 'NULL') . "\n";
    echo "  CPF: " . ($pdd['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "  Nome: " . ($pdd['nome'] ?: 'NULL') . "\n";
    echo "  Rua: " . ($pdd['rua'] ?: 'NULL') . "\n";
    echo "  Numero: " . ($pdd['numero'] ?: 'NULL') . "\n";
    echo "  Bairro: " . ($pdd['bairro'] ?: 'NULL') . "\n";
    echo "  CEP: " . ($pdd['cep'] ?: 'NULL') . "\n";
    echo "  Cidade: " . ($pdd['cidade'] ?: 'NULL') . "\n";
    echo "  Estado: " . ($pdd['estado'] ?: 'NULL') . "\n";
    echo "  Endereco (concat): " . ($pdd['endereco'] ?: 'NULL') . "\n\n";
} else {
    echo "⚠ Contrato 1987 NÃO encontrado em pdd_perdas!\n\n";
}

// Verificar spc_inclusos
$stmt = $db->prepare("SELECT 
    contrato,
    cpf_cnpj, 
    contratante, 
    rua, 
    numero,
    bairro,
    cep,
    cidade,
    estado
FROM spc_inclusos WHERE contrato = ?");
$stmt->execute(['1987']);
$spc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($spc) {
    echo "📋 spc_inclusos:\n";
    echo "  Contrato: " . ($spc['contrato'] ?: 'NULL') . "\n";
    echo "  CPF: " . ($spc['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "  Contratante: " . ($spc['contratante'] ?: 'NULL') . "\n";
    echo "  Rua: " . ($spc['rua'] ?: 'NULL') . "\n";
    echo "  Numero: " . ($spc['numero'] ?: 'NULL') . "\n";
    echo "  Bairro: " . ($spc['bairro'] ?: 'NULL') . "\n";
    echo "  CEP: " . ($spc['cep'] ?: 'NULL') . "\n";
    echo "  Cidade: " . ($spc['cidade'] ?: 'NULL') . "\n";
    echo "  Estado: " . ($spc['estado'] ?: 'NULL') . "\n\n";
} else {
    echo "⚠ Contrato 1987 NÃO encontrado em spc_inclusos!\n\n";
}

echo "✅ Agora REIMPORTE a planilha e execute novamente para ver as mudanças!\n";
