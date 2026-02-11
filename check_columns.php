<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Columns spc_inclusos ===\n";
$stmt = $db->query("DESCRIBE spc_inclusos");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . "\n";

echo "\n=== Columns spc_historico_removidos ===\n";
$stmt = $db->query("DESCRIBE spc_historico_removidos");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . "\n";
