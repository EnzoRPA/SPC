<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

echo "=== COUNTING MISMATCHES ===\n";
$sql = "
    SELECT COUNT(*) as cnt
    FROM spc_inclusos s
    JOIN parcelas_em_aberto p ON s.contrato_norm = p.contrato_norm
    WHERE s.cpf_cnpj_norm != p.cpf_cnpj_norm
";
$stmt = $db->query($sql);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n=== CHECKING IF PRESENCE IN SPC_EXCLUIDOS FILTERS THEM ===\n";
$sql = "
    SELECT COUNT(*) as cnt
    FROM spc_inclusos s
    JOIN parcelas_em_aberto p ON s.contrato_norm = p.contrato_norm
    JOIN spc_excluidos ex ON s.cpf_cnpj_norm = ex.cpf_cnpj_norm AND s.contrato_norm = ex.contrato_norm
    WHERE s.cpf_cnpj_norm != p.cpf_cnpj_norm
";
$stmt = $db->query($sql);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
