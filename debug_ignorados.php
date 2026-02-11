<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Checking spc_ignorados ===\n";

try {
    $stmt = $db->query("SELECT * FROM spc_ignorados");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($rows) . "\n";
    print_r($rows);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
