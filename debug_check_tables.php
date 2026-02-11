<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "Checking tables...\n";
$stmt = $db->query("SHOW TABLES LIKE 'spc_excluidos'");
$tableExists = $stmt->fetch();
echo "Table 'spc_excluidos' exists: " . ($tableExists ? 'YES' : 'NO') . "\n";

$stmt = $db->query("SHOW TABLES LIKE 'spc_historico_removidos'");
$tableExists = $stmt->fetch();
echo "Table 'spc_historico_removidos' exists: " . ($tableExists ? 'YES' : 'NO') . "\n";

echo "\nChecking records for contract 63818...\n";
echo "spc_inclusos:\n";
$stmt = $db->prepare("SELECT id, cpf_cnpj, contrato, vencimento, valor_debito, data_inclusao FROM spc_inclusos WHERE contrato = '63818' OR contrato LIKE '%63818%'");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);

echo "\nspc_historico_removidos:\n";
$stmt = $db->prepare("SELECT id, original_id, cpf_cnpj, contrato, vencimento, valor, data_remocao FROM spc_historico_removidos WHERE contrato = '63818' OR contrato LIKE '%63818%'");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);
