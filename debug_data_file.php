<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$log = "";

$log .= "=== Columns spc_inclusos ===\n";
$stmt = $db->query("DESCRIBE spc_inclusos");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
$log .= implode(", ", $cols) . "\n";

$log .= "\n=== Data Contract 63818 (spc_inclusos) ===\n";
$stmt = $db->prepare("SELECT contrato, cpf_cnpj, vencimento FROM spc_inclusos WHERE contrato LIKE '%63818%' LIMIT 5");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $log .= "C: '{$r['contrato']}', CPF: '{$r['cpf_cnpj']}', V: '{$r['vencimento']}'\n";
}

$log .= "\n=== Data Contract 63818 (spc_historico_removidos) ===\n";
$stmt = $db->prepare("SELECT contrato, cpf_cnpj, vencimento FROM spc_historico_removidos WHERE contrato LIKE '%63818%' LIMIT 5");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $log .= "C: '{$r['contrato']}', CPF: '{$r['cpf_cnpj']}', V: '{$r['vencimento']}'\n";
}

file_put_contents(__DIR__ . '/debug_output_full.txt', $log);
echo "Done. Check debug_output_full.txt\n";
