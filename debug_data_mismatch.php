<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$contract = '63818';

echo "=== SPC INCLUSOS (Active) ===\n";
$stmt = $db->prepare("SELECT id, cpf_cnpj, contrato, vencimento, valor_debito FROM spc_inclusos WHERE contrato LIKE ?");
$stmt->execute(["%$contract%"]);
$inclusos = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($inclusos);

echo "\n=== SPC HISTORICO REMOVIDOS (Removed) ===\n";
$stmt = $db->prepare("SELECT id, cpf_cnpj, contrato, vencimento, valor FROM spc_historico_removidos WHERE contrato LIKE ?");
$stmt->execute(["%$contract%"]);
$removidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($removidos);

if (!empty($inclusos) && !empty($removidos)) {
    echo "\n=== COMPARING FIRST MATCHES ===\n";
    $inc = $inclusos[0];
    foreach ($removidos as $rem) {
        echo "Checking Removido ID: " . $rem['id'] . "\n";
        echo "Contrato: '" . $inc['contrato'] . "' vs '" . $rem['contrato'] . "' -> " . ($inc['contrato'] == $rem['contrato'] ? "MATCH" : "FAIL") . "\n";
        echo "CPF: '" . $inc['cpf_cnpj'] . "' vs '" . $rem['cpf_cnpj'] . "' -> " . ($inc['cpf_cnpj'] == $rem['cpf_cnpj'] ? "MATCH" : "FAIL") . "\n";
        echo "Vencimento: '" . $inc['vencimento'] . "' vs '" . $rem['vencimento'] . "' -> " . ($inc['vencimento'] == $rem['vencimento'] ? "MATCH" : "FAIL") . "\n";
    }
}
