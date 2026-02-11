<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$log = "=== Columns parcelas_em_aberto ===\n";
$stmt = $db->query("DESCRIBE parcelas_em_aberto");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    $log .= $c['Field'] . " | " . $c['Type'] . " | " . $c['Null'] . " | " . $c['Key'] . " | " . $c['Default'] . " | " . $c['Extra'] . "\n";
}
file_put_contents('parcelas_schema.txt', $log);
echo "Done.";
