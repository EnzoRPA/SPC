<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT batch_id FROM parcelas_em_aberto LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "Valid batch_id: " . $row['batch_id'] . "\n";
} else {
    echo "No batch_id found (table empty?).\n";
}
