<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "=== UPDATING PDD_PERDAS SCHEMA ===\n";

try {
    // Add VALOR
    $db->exec("ALTER TABLE pdd_perdas ADD COLUMN valor DECIMAL(15,2) DEFAULT 0.00 AFTER data_vencimento");
    echo "Added 'valor' column.\n";
} catch (PDOException $e) {
    echo "Valor column might already exist or error: " . $e->getMessage() . "\n";
}

try {
    // Add NOME
    $db->exec("ALTER TABLE pdd_perdas ADD COLUMN nome VARCHAR(255) NULL AFTER codigo_contrato");
    echo "Added 'nome' column.\n";
} catch (PDOException $e) {
    echo "Nome column might already exist or error: " . $e->getMessage() . "\n";
}

// Verify
$stmt = $db->query("DESCRIBE pdd_perdas");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "Column: {$col['Field']} ({$col['Type']})\n";
}
