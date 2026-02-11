<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$contract = '63818';
$cpf = '05201555306';

echo "Checking spc_inclusos for duplicates:\n";
$stmt = $db->prepare("SELECT id, cpf_cnpj, contrato, vencimento, debito FROM spc_inclusos WHERE contrato LIKE ? AND cpf_cnpj LIKE ?");
$stmt->execute(["%$contract%", "%$cpf%"]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
