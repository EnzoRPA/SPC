<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== DATABASE CONNECTION INFO ===\n";
echo "Host: localhost\n";
echo "Database: spc_control\n\n";

echo "=== CHECKING TABLES ===\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "✓ $table\n";
}

echo "\n=== SPC_INCLUSOS COLUMNS ===\n";
try {
    $stmt = $db->query("DESCRIBE spc_inclusos");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-30s %s\n", $row['Field'], $row['Type'], $row['Null']);
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING FOR 'status' COLUMN ===\n";
$stmt = $db->query("SHOW COLUMNS FROM spc_inclusos LIKE 'status'");
$statusCol = $stmt->fetch();
if ($statusCol) {
    echo "✓ Column 'status' EXISTS\n";
    echo "  Type: " . $statusCol['Type'] . "\n";
} else {
    echo "✗ Column 'status' NOT FOUND!\n";
}
