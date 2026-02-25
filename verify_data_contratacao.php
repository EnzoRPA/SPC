<?php
require_once __DIR__ . '/config/db.php';
$db = (new Database())->getConnection();

echo "=== VERIFICAÇÃO: Data Contratação em pdd_perdas ===\n\n";

// 1. Check if column exists
$stmt = $db->query("DESCRIBE pdd_perdas");
$hasColumn = false;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['Field'] === 'data_contratacao') {
        $hasColumn = true;
        echo "✓ Coluna 'data_contratacao' existe.\n";
        break;
    }
}

if (!$hasColumn) {
    echo "✗ Coluna 'data_contratacao' NÃO ENCONTRADA!\n";
    exit;
}

// 2. Check if any record has data_contratacao filled (might be empty if no import yet)
$stmt = $db->query("SELECT COUNT(*) as total FROM pdd_perdas WHERE data_contratacao IS NOT NULL");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Registros com Data Contratação preenchida: $total\n";

if ($total == 0) {
    echo "Nota: Nenhum registro tem data ainda. Isso é esperado até que uma nova importação seja feita.\n";
} else {
    echo "Sucesso! Já existem registros com data.\n";
    
    // Show a sample
    $stmt = $db->query("SELECT codigo_contrato, data_contratacao FROM pdd_perdas WHERE data_contratacao IS NOT NULL LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo " - Contrato: {$row['codigo_contrato']}, Data: {$row['data_contratacao']}\n";
    }
}
