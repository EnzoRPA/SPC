<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== IMPORT_BATCHES TABLE CHECK ===\n\n";

try {
    $stmt = $db->query("DESCRIBE import_batches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo sprintf("%-20s %-30s\n", $col['Field'], $col['Type']);
    }
    
    $hasStatus = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $hasStatus = true;
            break;
        }
    }
    
    if ($hasStatus) {
        echo "\n✓ 'status' column EXISTS in import_batches\n";
    } else {
        echo "\n✗ 'status' column MISSING in import_batches!\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
