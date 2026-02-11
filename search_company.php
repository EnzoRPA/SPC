<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Searching for 'Gees S A' ===\n";
$sql = "SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, debito 
        FROM parcelas_em_aberto 
        WHERE contratante LIKE '%Gees%' 
        ORDER BY cpf_cnpj 
        LIMIT 20";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No records found.\n";
} else {
    foreach ($rows as $r) {
        print_r($r);
    }
}
