<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "=== CHECKING PDD PERDAS CONTENT ===\n";

$sql = "SELECT COUNT(*) FROM pdd_perdas";
$count = $db->query($sql)->fetchColumn();
echo "Total rows: $count\n";

$sql = "SELECT id, codigo_contrato, nome, valor, data_vencimento FROM pdd_perdas LIMIT 10";
$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sample Data:\n";
foreach ($rows as $row) {
    print_r($row);
}

// Check for 0 values
$sql = "SELECT COUNT(*) FROM pdd_perdas WHERE valor = 0 OR valor IS NULL";
$zeros = $db->query($sql)->fetchColumn();
echo "\nRows with Valor = 0: $zeros\n";

// Check for NULL names
$sql = "SELECT COUNT(*) FROM pdd_perdas WHERE nome IS NULL OR nome = ''";
$nullNames = $db->query($sql)->fetchColumn();
echo "Rows with Empty Name: $nullNames\n";
