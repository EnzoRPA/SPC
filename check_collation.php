<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Checking Table Collations ===\n";

$tables = ['parcelas_em_aberto', 'spc_ignorados', 'spc_inclusos'];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Table: $table\n";
        echo $result['Create Table'] . "\n\n";
    } catch (PDOException $e) {
        echo "Error checking $table: " . $e->getMessage() . "\n";
    }
}
