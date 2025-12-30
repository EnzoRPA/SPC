<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING FOR COLUMN 'type' IN ALL TABLES ===\n\n";

$tables = ['import_batches', 'spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos'];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Table: $table\n";
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'type' || $col['Field'] === 'tp_contrato' || $col['Field'] === 'situacao') {
                echo "  - Column: " . $col['Field'] . " (" . $col['Type'] . ")\n";
                $found = true;
            }
        }
        if (!$found) {
            echo "  (No 'type' or similar column found)\n";
        }
        echo "\n";
    } catch (PDOException $e) {
        echo "Table $table not found or error: " . $e->getMessage() . "\n\n";
    }
}
