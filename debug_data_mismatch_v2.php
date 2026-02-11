<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$contract = '63818';

try {
    echo "=== SPC INCLUSOS (Active) ===\n";
    $stmt = $db->prepare("SELECT * FROM spc_inclusos WHERE contrato LIKE ?");
    $stmt->execute(["%$contract%"]);
    $inclusos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($inclusos) . " records.\n";
    if (count($inclusos) > 0) print_r($inclusos[0]); // Show first

    echo "\n=== SPC HISTORICO REMOVIDOS (Removed) ===\n";
    $stmt = $db->prepare("SELECT * FROM spc_historico_removidos WHERE contrato LIKE ?");
    $stmt->execute(["%$contract%"]);
    $removidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($removidos) . " records.\n";
    if (count($removidos) > 0) print_r($removidos[0]); // Show first

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
