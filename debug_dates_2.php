<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "Bad Dates in Parcelas:\n";
$stmt = $db->query("SELECT contratacao, emissao, vencimento FROM parcelas_em_aberto WHERE YEAR(vencimento) < 1900 LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "No bad dates found in parcelas_em_aberto.\n";
} else {
    print_r($rows);
}

echo "\nBad Dates in SPC Inclusos:\n";
$stmt = $db->query("SELECT data_inclusao, vencimento FROM spc_inclusos WHERE YEAR(vencimento) < 1900 LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "No bad dates found in spc_inclusos.\n";
} else {
    print_r($rows);
}
