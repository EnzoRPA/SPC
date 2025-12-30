<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();

$contrato = '49011';
$cpf = '41333756372';

echo "=== CHECKING CONTRACT $contrato ===\n";
$stmt = $db->prepare("SELECT * FROM parcelas_em_aberto WHERE contrato_norm = ?");
$stmt->execute([$contrato]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(empty($rows)) echo "No match for contract.\n";
else print_r($rows);

echo "\n=== CHECKING CPF $cpf ===\n";
$stmt = $db->prepare("SELECT * FROM parcelas_em_aberto WHERE cpf_cnpj_norm = ?");
$stmt->execute([$cpf]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(empty($rows)) echo "No match for CPF.\n";
else print_r($rows);
