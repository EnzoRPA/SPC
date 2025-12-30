<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== FINDING CANDIDATES FOR EXCLUSION (In Inclusos BUT NOT IN Parcelas) ===\n";
// We match by normalized CPF and Contract
$sql = "
    SELECT i.id, i.cpf_cnpj, i.contrato, i.cpf_cnpj_norm, i.contrato_norm
    FROM spc_inclusos i
    LEFT JOIN parcelas_em_aberto p 
    ON i.cpf_cnpj_norm = p.cpf_cnpj_norm AND i.contrato_norm = p.contrato_norm
    WHERE p.id IS NULL
    LIMIT 3
";
$stmt = $db->query($sql);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($candidates);

echo "\n=== FINDING CANDIDATES FOR INCLUSION (In Parcelas BUT NOT IN Inclusos) ===\n";
// Filtering logic: > 5 days delay, > 150 reais, etc might apply, but let's just check raw existence first
$sql = "
    SELECT p.id, p.cpf_cnpj, p.contrato, p.cpf_cnpj_norm, p.contrato_norm
    FROM parcelas_em_aberto p
    LEFT JOIN spc_inclusos i 
    ON p.cpf_cnpj_norm = i.cpf_cnpj_norm AND p.contrato_norm = i.contrato_norm
    WHERE i.id IS NULL
    LIMIT 3
";
$stmt = $db->query($sql);
$candidatesInc = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($candidatesInc);
