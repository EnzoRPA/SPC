<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Searching for mismatch: CNPJ in Display col, CPF in Norm col ===\n";

// Logic: 
// 1. cpf_cnpj has length > 14 (assuming puntuation) or > 11 digits
// 2. cpf_cnpj_norm has length <= 11

$sql = "SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, cpf_cnpj_norm, debito 
        FROM spc_inclusos 
        WHERE CHAR_LENGTH(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')) > 11
        AND CHAR_LENGTH(cpf_cnpj_norm) <= 11";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No mismatch records found matching that criteria.\n";
} else {
    $log = "Found " . count($rows) . " mismatch records:\n";
    foreach ($rows as $r) {
        $log .= print_r($r, true);
    }
}

$log .= "\n=== Also checking Demitidos specifically ===\n";
$sql2 = "SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, cpf_cnpj_norm 
         FROM spc_inclusos 
         WHERE (tp_contrato LIKE '%Demitidos%' OR tp_contrato LIKE '%Aposentados%')
         LIMIT 20";
$stmt2 = $db->prepare($sql2);
$stmt2->execute();
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows2 as $r) {
    $log .= print_r($r, true);
}

file_put_contents('mismatch_output.txt', $log);
echo "Done.";
