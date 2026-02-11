<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "Checking pdd_perdas columns:\n";
$stmt = $db->query("DESCRIBE pdd_perdas");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cols as $col) {
    echo "Column: {$col['Field']} - Type: {$col['Type']}\n";
}
