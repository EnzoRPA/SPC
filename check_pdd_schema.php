<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING PDD_PERDAS SCHEMA ===\n\n";

try {
    $stmt = $db->query("DESCRIBE pdd_perdas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCodigoVenda = false;
    foreach ($columns as $col) {
        echo "Column: " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'codigo_venda') {
            $hasCodigoVenda = true;
        }
    }
    
    if ($hasCodigoVenda) {
        echo "\n✓ 'codigo_venda' ALREADY EXISTS.\n";
    } else {
        echo "\n✗ 'codigo_venda' MISSING. Adding it now...\n";
        $db->exec("ALTER TABLE pdd_perdas ADD COLUMN codigo_venda VARCHAR(100) AFTER batch_id");
        echo "✓ 'codigo_venda' ADDED SUCCESSFULLY.\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
