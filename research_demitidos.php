<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$terms = ['DEMITIDO', 'APOSENTADO'];

foreach ($terms as $term) {
    echo "=== Searching for '$term' ===\n";
    // Check contract, type, and contractor name
    $sql = "SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, debito 
            FROM parcelas_em_aberto 
            WHERE contrato LIKE ? OR tp_contrato LIKE ? OR contratante LIKE ? 
            LIMIT 5";
    $stmt = $db->prepare($sql);
    $param = "%$term%";
    $stmt->execute([$param, $param, $param]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "No records found.\n";
    } else {
        foreach ($rows as $r) {
            print_r($r);
        }
    }
    echo "\n";
}
