<?php
// Comprehensive database check
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== COMPREHENSIVE DATABASE CHECK ===\n\n";

$tables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            echo sprintf("  %-25s %s\n", $col['Field'], $col['Type']);
        }
        
        // Check for 'status' column specifically
        $hasStatus = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'status') {
                $hasStatus = true;
                break;
            }
        }
        
        if ($hasStatus) {
            echo "  ✓ Has 'status' column\n";
        } else {
            echo "  ✗ MISSING 'status' column!\n";
        }
        
    } catch (PDOException $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}
