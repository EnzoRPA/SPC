<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

$log = "=== Lista de Demitidos e Aposentados ===\n";
$log .= str_pad("Contrato", 15) . " | " . str_pad("Tipo", 25) . " | " . str_pad("Nome/Contratante", 30) . " | " . str_pad("CPF/CNPJ", 18) . " | Valor\n";
$log .= str_repeat("-", 100) . "\n";

$sql = "SELECT contrato, tp_contrato, contratante, cpf_cnpj, debito 
        FROM parcelas_em_aberto 
        WHERE tp_contrato LIKE '%Demitidos%' OR tp_contrato LIKE '%Aposentados%'
        ORDER BY contratante, contrato";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    $log .= "Nenhum registro encontrado.\n";
} else {
    foreach ($rows as $r) {
        $log .= str_pad(substr($r['contrato'], 0, 15), 15) . " | " 
           . str_pad(substr($r['tp_contrato'], 0, 25), 25) . " | " 
           . str_pad(substr($r['contratante'], 0, 30), 30) . " | " 
           . str_pad($r['cpf_cnpj'], 18) . " | " 
           . number_format((float)$r['debito'], 2, ',', '.') . "\n";
    }
    $log .= "\nTotal de registros: " . count($rows) . "\n";
}

file_put_contents('list_demitidos_output.txt', $log);
echo "Output saved to list_demitidos_output.txt\n";
