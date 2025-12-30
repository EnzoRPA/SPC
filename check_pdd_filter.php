<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

echo "=== CHECKING PDD PERDAS FILTER ===\n";
$sql = "
    SELECT COUNT(*) as cnt
    FROM spc_inclusos s
    JOIN parcelas_em_aberto p ON s.contrato_norm = p.contrato_norm
    JOIN pdd_perdas pp ON s.contrato_norm = pp.codigo_contrato_norm
    WHERE s.cpf_cnpj_norm != p.cpf_cnpj_norm
";
$stmt = $db->query($sql);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
