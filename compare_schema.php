<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== SPC_INCLUSOS TABLE ANALYSIS ===\n\n";

// Get all columns
$stmt = $db->query("DESCRIBE spc_inclusos");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total columns: " . count($columns) . "\n\n";

echo "Column list:\n";
$colNames = [];
foreach ($columns as $col) {
    $colNames[] = $col['Field'];
    echo sprintf("  %2d. %-25s %s\n", count($colNames), $col['Field'], $col['Type']);
}

echo "\n=== COLUMNS IN SpcImporter.php INSERT ===\n";
$importerCols = [
    'batch_id', 'contrato', 'tp_contrato', 'contratante', 'contratacao', 'cpf_cnpj', 'status',
    'venda', 'parcela', 'debito', 'emissao', 'vencimento', 'dias_atraso',
    'rua', 'numero', 'bairro', 'cep', 'cidade', 'estado',
    'nascimento', 'linha', 'status_inclusao', 'data_inclusao', 'hora_inclusao',
    'cpf_cnpj_norm', 'contrato_norm'
];

echo "Total columns in INSERT: " . count($importerCols) . "\n\n";

foreach ($importerCols as $i => $col) {
    echo sprintf("  %2d. %s\n", $i + 1, $col);
}

echo "\n=== COMPARISON ===\n";

// Remove 'id' from table columns for comparison
$tableColsNoId = array_filter($colNames, function($col) { return $col !== 'id'; });
$tableColsNoId = array_values($tableColsNoId);

echo "Table columns (excluding id): " . count($tableColsNoId) . "\n";
echo "Importer columns: " . count($importerCols) . "\n\n";

// Find missing columns
$missing = array_diff($tableColsNoId, $importerCols);
$extra = array_diff($importerCols, $tableColsNoId);

if (count($missing) > 0) {
    echo "✗ Columns in TABLE but NOT in IMPORTER:\n";
    foreach ($missing as $col) {
        echo "  - $col\n";
    }
    echo "\n";
}

if (count($extra) > 0) {
    echo "✗ Columns in IMPORTER but NOT in TABLE:\n";
    foreach ($extra as $col) {
        echo "  - $col\n";
    }
    echo "\n";
}

if (count($missing) === 0 && count($extra) === 0) {
    echo "✓ All columns match!\n";
}
