<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$cpf = '05201555306'; // The remaining one
$cpfFormatted = '052.015.553-06';
$cpfPartial = '052015553';

echo "Searching for CPF $cpf in spc_historico_removidos...\n";

$sql = "SELECT * FROM spc_historico_removidos WHERE cpf_cnpj LIKE ? OR cpf_cnpj LIKE ?";
$stmt = $db->prepare($sql);
$stmt->execute(["%$cpf%", "%$cpfFormatted%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($results) . " records.\n";
print_r($results);
