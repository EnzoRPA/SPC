<?php
require_once 'config/db.php';
require_once 'src/Comparator.php';

use App\Comparator;

$database = new Database();
$db = $database->getConnection();

$comparator = new Comparator($db);
echo "=== DUMPING SEM PARCELA RECORDS ===\n";
$exclusao = $comparator->obterParaExclusao();

foreach ($exclusao as $row) {
    if ($row['motivo'] == 'Sem Parcela') {
        echo "ID: {$row['id']} | CPF: {$row['cpf_cnpj']} | Contrato: {$row['contrato']} | Venc: {$row['vencimento']} | Valor: {$row['valor']}\n";
    }
}
