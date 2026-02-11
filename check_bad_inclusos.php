<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Searching spc_inclusos for 'Demitidos' with CNPJ > 11 ===\n";

$sql = "SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, debito 
        FROM spc_inclusos
        WHERE (tp_contrato LIKE '%Demitidos%' OR tp_contrato LIKE '%Aposentados%')
        AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')) > 11";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No bad records in spc_inclusos either.\n";
} else {
    echo "Found " . count($rows) . " bad records in spc_inclusos:\n";
    foreach ($rows as $r) {
        print_r($r);
    }
}
