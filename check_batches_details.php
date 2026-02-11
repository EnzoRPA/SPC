<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "Checking import_batches schema:\n";
$stmt = $db->query("DESCRIBE import_batches");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns: " . implode(", ", $cols) . "\n\n";

echo "Checking specific batches found in pdd_perdas:\n";
$sql = "SELECT * FROM import_batches WHERE id IN (1, 95, 115, 128)"; // Using IDs seen before + 1 just in case
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    print_r($row);
}
