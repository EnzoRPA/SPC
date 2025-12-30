<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$contrato = '26892';
echo "=== CHECKING CONTRATO $contrato IN PARCELAS ===\n";

$stmt = $db->prepare("SELECT * FROM parcelas_em_aberto WHERE contrato_norm = ?");
$stmt->execute([$contrato]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No match found for contract $contrato in parcelas_em_aberto.\n";
} else {
    print_r($rows);
}
