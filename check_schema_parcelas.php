<?php
require 'config/db.php';
$db = (new Database())->getConnection();
echo "--- parcelas_em_aberto ---\n";
$stmt = $db->query("DESCRIBE parcelas_em_aberto");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}

echo "\n--- pdd_perdas ---\n";
$stmt = $db->query("DESCRIBE pdd_perdas");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}
