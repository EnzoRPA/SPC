<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== VERIFICAÇÃO: Colunas de endereço em pdd_perdas ===\n\n";

// Listar colunas da tabela
echo "1️⃣ Colunas da tabela pdd_perdas:\n";
$stmt = $db->query("DESCRIBE pdd_perdas");
$columns = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
    if (in_array($row['Field'], ['rua', 'numero', 'bairro', 'cep', 'cidade', 'estado', 'endereco'])) {
        echo "   ✓ {$row['Field']} ({$row['Type']})\n";
    }
}
echo "\n";

// Ver dados de um contrato que foi importado
echo "2️⃣ Dados do contrato 1987 (recém importado):\n";
$sql << "SELECT 
    contrato,
    rua, 
    numero,
    bairro, 
    cep,
    cidade,
    estado,
    endereco 
FROM pdd_perdas 
WHERE codigo_contrato = '1987' 
LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    foreach ($row as $col => $val) {
        echo "   $col: " . ($val ?: 'NULL') . "\n";
    }
} else {
    echo "   Nenhum registro encontrado\n";
}

echo "\n3️⃣ Contagem de registros COM dados individuais:\n";
$stmt = $db->query("SELECT COUNT(*) as total FROM pdd_perdas WHERE rua IS NOT NULL AND rua != ''");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   Registros com RUA preenchida: $total\n";

$stmt = $db->query("SELECT COUNT(*) as total FROM pdd_perdas");
$totalGeral = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "   Total de registros: $totalGeral\n";
