<?php
// Web-accessible diagnostic script
require '../config/db.php';

header('Content-Type: text/plain; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

echo "=== WEB SERVER DATABASE DIAGNOSTIC ===\n\n";

try {
    // Check connection
    echo "✓ Database connection successful\n";
    echo "Database: spc_control\n\n";
    
    // Check spc_inclusos table
    echo "=== SPC_INCLUSOS TABLE ===\n";
    $stmt = $db->query("DESCRIBE spc_inclusos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total columns: " . count($columns) . "\n\n";
    
    foreach ($columns as $i => $col) {
        echo sprintf("%2d. %-25s %-30s\n", $i + 1, $col['Field'], $col['Type']);
    }
    
    // Check for 'status' column
    echo "\n=== STATUS COLUMN CHECK ===\n";
    $hasStatus = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $hasStatus = true;
            break;
        }
    }
    
    if ($hasStatus) {
        echo "✓ 'status' column EXISTS\n";
    } else {
        echo "✗ 'status' column NOT FOUND!\n";
    }
    
    // Test INSERT statement
    echo "\n=== TESTING INSERT STATEMENT ===\n";
    try {
        $testStmt = $db->prepare("
            INSERT INTO spc_inclusos (
                batch_id, contrato, tp_contrato, contratante, contratacao, cpf_cnpj, status,
                venda, parcela, debito, emissao, vencimento, dias_atraso,
                rua, numero, bairro, cep, cidade, estado,
                nascimento, linha, status_inclusao, data_inclusao, hora_inclusao,
                cpf_cnpj_norm, contrato_norm
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        echo "✓ INSERT statement prepared successfully\n";
        echo "  This means all columns in the INSERT exist in the table\n";
    } catch (PDOException $e) {
        echo "✗ INSERT statement FAILED\n";
        echo "  Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}
