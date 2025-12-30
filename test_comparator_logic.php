<?php
require_once 'config/db.php';
require_once 'src/Comparator.php';

use App\Comparator;

$database = new Database();
$db = $database->getConnection();

$comparator = new Comparator($db);
echo "=== TESTING COMPARATOR LOGIC ===\n";
$exclusao = $comparator->obterParaExclusao();

$divergentes = 0;
$semParcela = 0;
$pddPago = 0;

foreach ($exclusao as $row) {
    if ($row['motivo'] == 'CPF Divergente') {
        $divergentes++;
        if ($divergentes <= 5) {
            echo "Found Divergent: ID {$row['id']} | Contrato {$row['contrato']} | CPF {$row['cpf_cnpj']} | Motivo: {$row['motivo']}\n";
        }
    } elseif ($row['motivo'] == 'Sem Parcela') {
        $semParcela++;
    } elseif ($row['motivo'] == 'PDD PAGO') {
        $pddPago++;
    }
}

echo "\nSummary:\n";
echo "CPF Divergente: $divergentes\n";
echo "Sem Parcela: $semParcela\n";
echo "PDD PAGO: $pddPago\n";
echo "Total: " . count($exclusao) . "\n";
