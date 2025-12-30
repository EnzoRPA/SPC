<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("DESCRIBE spc_inclusos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in spc_inclusos:\n";
    foreach ($columns as $col) {
        echo "- $col\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
