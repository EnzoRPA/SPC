<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== FIXING IMPORT_BATCHES TABLE ===\n\n";

try {
    // 1. Check current type
    echo "1. Current schema:\n";
    $stmt = $db->query("DESCRIBE import_batches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'type') {
            echo "   - Column 'type': " . $col['Type'] . "\n";
        }
    }

    // 2. Modify column
    echo "\n2. Modifying column to VARCHAR(50)...\n";
    $db->exec("ALTER TABLE import_batches MODIFY COLUMN type VARCHAR(50)");
    echo "   - SUCCESS: Column modified.\n";

    // 3. Verify change
    echo "\n3. New schema:\n";
    $stmt = $db->query("DESCRIBE import_batches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'type') {
            echo "   - Column 'type': " . $col['Type'] . "\n";
        }
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
