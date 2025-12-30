<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== PARCELAS_EM_ABERTO COLUMNS ===\n";
try {
    $stmt = $db->query("DESCRIBE parcelas_em_aberto");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-30s\n", $row['Field'], $row['Type']);
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING FOR 'status' COLUMN ===\n";
$stmt = $db->query("SHOW COLUMNS FROM parcelas_em_aberto LIKE 'status'");
$statusCol = $stmt->fetch();
if ($statusCol) {
    echo "✓ Column 'status' EXISTS\n";
} else {
    echo "✗ Column 'status' NOT FOUND in parcelas_em_aberto!\n";
    echo "\nThis is the problem! ParcelasImporter.php tries to insert 'status' but the column doesn't exist.\n";
}
