<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING NORMALIZATION STATS ===\n";

// Check CPF lengths in spc_inclusos
$sql = "SELECT LENGTH(cpf_cnpj_norm) as len, COUNT(*) as cnt FROM spc_inclusos GROUP BY LENGTH(cpf_cnpj_norm)";
$stmt = $db->query($sql);
echo "SPC Inclusos CPF Lengths:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// Check CPF lengths in parcelas_em_aberto
$sql = "SELECT LENGTH(cpf_cnpj_norm) as len, COUNT(*) as cnt FROM parcelas_em_aberto GROUP BY LENGTH(cpf_cnpj_norm)";
$stmt = $db->query($sql);
echo "\nParcelas CPF Lengths:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// Check Contract format (numeric vs alphanumeric)
$sql = "SELECT contrato_norm FROM spc_inclusos WHERE contrato_norm REGEXP '^0' LIMIT 5";
$stmt = $db->query($sql);
echo "\nSPC Contracts starting with 0 (should be none if ltrim works):\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// Check for mismatches where stripping leading zeros would match
echo "\nChecking for records that match if we strip leading zeros from CPF...\n";
$sql = "
    SELECT s.cpf_cnpj_norm as spc_cpf, p.cpf_cnpj_norm as parc_cpf
    FROM spc_inclusos s
    JOIN parcelas_em_aberto p ON s.contrato_norm = p.contrato_norm
    WHERE s.cpf_cnpj_norm != p.cpf_cnpj_norm
    AND CAST(s.cpf_cnpj_norm AS UNSIGNED) = CAST(p.cpf_cnpj_norm AS UNSIGNED)
    LIMIT 10
";
$stmt = $db->query($sql);
$mismatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($mismatches);
