<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== MIGRATION RETRY: Add data_contratacao to pdd_perdas ===\n";

try {
    // Check if column exists
    $stmt = $db->query("DESCRIBE pdd_perdas");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('data_contratacao', $columns)) {
        echo "Column 'data_contratacao' already exists.\n";
    } else {
        echo "Adding column 'data_contratacao'...\n";
        // REMOVED 'AFTER contrato_norm' to reduce potential friction if schema varies
        $sql = "ALTER TABLE pdd_perdas ADD COLUMN data_contratacao DATE DEFAULT NULL"; 
        $db->exec($sql);
        echo "Column added successfully.\n";
    }

} catch (PDOException $e) {
    echo "ERROR executing SQL: " . $e->getMessage() . "\n";
    exit(1);
}
