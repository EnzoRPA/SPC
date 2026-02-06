<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "--- pdd_perdas ---\n";
$stmt = $db->query("DESCRIBE pdd_perdas");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}

echo "\n--- pdd_pagos ---\n";
$stmt = $db->query("DESCRIBE pdd_pagos");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}
