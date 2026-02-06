<?php
require 'config/db.php';
require_once 'src/Comparator.php';
$db = (new Database())->getConnection();

echo "Checking history for contract 1987...\n";
$stmt = $db->prepare("SELECT * FROM spc_historico_removidos WHERE contrato = ? OR contrato LIKE ?");
$stmt->execute(['1987', '%1987%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    echo "Found " . count($rows) . " rows.\n";
    print_r($rows[0]);
} else {
    echo "Not found in history.\n";
}

// Check other matches in PDD Inclusion list that MIGHT have worked?
echo "\nChecking if ANY PDD inclusion item got valid CPF...\n";
$comp = new \App\Comparator($db);
$inclusao = $comp->obterParaInclusao();
$hydrated = 0;
foreach ($inclusao as $item) {
    if ($item['motivo'] === 'PDD PERDAS (Importado)' && $item['cpf_cnpj'] !== 'CPF NAO ENCONTRADO') {
        $hydrated++;
        if ($hydrated === 1) {
            echo "First hydrated item:\n";
            print_r($item);
        }
    }
}
echo "Total hydrated items: $hydrated around " . count($inclusao) . "\n";
