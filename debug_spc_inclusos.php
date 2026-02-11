<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== INVESTIGAÇÃO: Por que spc_inclusos não atualiza? ===\n\n";

// 1. Verificar se contrato 1987 existe em spc_inclusos
echo "1️⃣ Procurando contrato 1987 em spc_inclusos:\n";
$stmt = $db->prepare("SELECT id, contrato, contrato_norm, cpf_cnpj, contratante FROM spc_inclusos WHERE contrato LIKE '%1987%' LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "   ❌ PROBLEMA: Nenhum registro com contrato '1987' encontrado!\n";
    echo "   O UPDATE não pode afetar nenhuma linha porque o registro não existe.\n\n";
} else {
    echo "   ✓ Encontrados " . count($results) . " registros:\n";
    foreach ($results as $row) {
        echo "     - ID: {$row['id']}, Contrato: '{$row['contrato']}', Norm: '{$row['contrato_norm']}', CPF: " . ($row['cpf_cnpj'] ?: 'NULL') . "\n";
    }
    echo "\n";
}

// 2. Verificar especificamente por contrato_norm = '1987'
echo "2️⃣ Procurando por contrato_norm = '1987':\n";
$stmt = $db->prepare("SELECT id, contrato, contrato_norm, cpf_cnpj, contratante FROM spc_inclusos WHERE contrato_norm = ?");
$stmt->execute(['1987']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "   ❌ PROBLEMA: Nenhum registro com contrato_norm = '1987'!\n";
    echo "   O UPDATE WHERE contrato_norm = '1987' não vai afetar nenhuma linha.\n\n";
} else {
    echo "   ✓ Encontrados " . count($results) . " registros:\n";
    foreach ($results as $row) {
        echo "     - ID: {$row['id']}, Contrato: '{$row['contrato']}', Norm: '{$row['contrato_norm']}', CPF: " . ($row['cpf_cnpj'] ?: 'NULL') . "\n";
    }
    echo "\n";
}

// 3. Comparar com pdd_perdas
echo "3️⃣ Comparando com pdd_perdas:\n";
$stmt = $db->prepare("SELECT id, contrato, codigo_contrato_norm, cpf_cnpj FROM pdd_perdas WHERE codigo_contrato_norm = ?");
$stmt->execute(['1987']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Registros em pdd_perdas com codigo_contrato_norm = '1987': " . count($results) . "\n";
if (!empty($results)) {
    foreach ($results as $row) {
        echo "     - ID: {$row['id']}, Contrato: '{$row['contrato']}', Norm: '{$row['codigo_contrato_norm']}'\n";
    }
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se spc_inclusos mostra 0 registros, então o problema é que o contrato 1987\n";
echo "não existe nessa tabela, ou está com um formato diferente de contrato_norm.\n";
