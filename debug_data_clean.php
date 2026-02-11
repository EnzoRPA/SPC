<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Columns spc_inclusos ===\n";
$stmt = $db->query("DESCRIBE spc_inclusos");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $cols) . "\n";

echo "\n=== Data Contract 63818 (spc_inclusos) ===\n";
$stmt = $db->prepare("SELECT contrato, cpf_cnpj, vencimento FROM spc_inclusos WHERE contrato LIKE '%63818%'");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "C: '{$r['contrato']}', CPF: '{$r['cpf_cnpj']}', V: '{$r['vencimento']}'\n";
}

echo "\n=== Data Contract 63818 (spc_historico_removidos) ===\n";
$stmt = $db->prepare("SELECT contrato, cpf_cnpj, vencimento FROM spc_historico_removidos WHERE contrato LIKE '%63818%'");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "C: '{$r['contrato']}', CPF: '{$r['cpf_cnpj']}', V: '{$r['vencimento']}'\n";
}
