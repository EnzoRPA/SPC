<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Searching for 'Demitidos' with CNPJ (length > 11) ===\n";

// Postgres/MySQL length check might differ if punctuation is present. 
// Assuming data in DB is 'clean' or has punctuation.
// Safe bet: remove non-digits and check length.

$sql = "SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, debito 
        FROM parcelas_em_aberto 
        WHERE (tp_contrato LIKE '%Demitidos%' OR tp_contrato LIKE '%Aposentados%')
        AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')) > 11";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No 'bad' records found. All Demitidos/Aposentados seem to have CPF (length <= 11).\n";
} else {
    echo "Found " . count($rows) . " potential bad records:\n";
    foreach ($rows as $r) {
        print_r($r);
    }
}
