<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$stmt = $db->query("DESCRIBE pdd_perdas");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($cols as $col) {
    echo "Column: $col\n";
}

// Check distinct batch_ids
$stmt = $db->query("SELECT DISTINCT batch_id FROM pdd_perdas LIMIT 10");
$batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Batches found: " . implode(", ", $batches) . "\n";
