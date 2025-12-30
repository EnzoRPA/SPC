<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING CONTRACT MATCHES WITH CPF MISMATCHES ===\n";
$sql = "
    SELECT s.contrato_norm, s.cpf_cnpj_norm as spc_cpf, p.cpf_cnpj_norm as parc_cpf
    FROM spc_inclusos s
    JOIN parcelas_em_aberto p ON s.contrato_norm = p.contrato_norm
    WHERE s.cpf_cnpj_norm != p.cpf_cnpj_norm
    LIMIT 20
";
$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);
