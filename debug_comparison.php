<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$cpf = '55807453387'; // From user report

echo "=== SPC INCLUSOS ===\n";
// Try searching by exact CPF and normalized
$stmt = $db->prepare("
    SELECT id, cpf_cnpj, cpf_cnpj_norm, contrato, contrato_norm, venda, vencimento, debito, data_inclusao 
    FROM spc_inclusos 
    WHERE cpf_cnpj LIKE ? OR cpf_cnpj_norm LIKE ?
");
$stmt->execute(["%$cpf%", "%$cpf%"]);
$inclusos = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($inclusos);

echo "\n=== PARCELAS EM ABERTO ===\n";
$stmt = $db->prepare("
    SELECT id, cpf_cnpj, cpf_cnpj_norm, contrato, contrato_norm, venda, vencimento, debito, contratante 
    FROM parcelas_em_aberto 
    WHERE cpf_cnpj LIKE ? OR cpf_cnpj_norm LIKE ?
");
$stmt->execute(["%$cpf%", "%$cpf%"]);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($parcelas);

// Check if any match by contract only
echo "\n=== CHECK BY CONTRACT (32801) ===\n";
$contract = '32801';
$stmt = $db->prepare("SELECT id, cpf_cnpj_norm, contrato_norm FROM spc_inclusos WHERE contrato LIKE ?");
$stmt->execute(["%$contract%"]);
$contractInclusos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Inclusos by contract:\n";
print_r($contractInclusos);

$stmt = $db->prepare("SELECT id, cpf_cnpj_norm, contrato_norm FROM parcelas_em_aberto WHERE contrato LIKE ?");
$stmt->execute(["%$contract%"]);
$contractParcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Parcelas by contract:\n";
print_r($contractParcelas);
