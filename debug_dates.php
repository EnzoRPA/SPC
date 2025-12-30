<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "Parcelas em Aberto:\n";
$stmt = $db->query("SELECT contratacao, emissao, vencimento FROM parcelas_em_aberto LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nSPC Inclusos:\n";
$stmt = $db->query("SELECT data_inclusao, vencimento FROM spc_inclusos LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
