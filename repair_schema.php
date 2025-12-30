<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Reparando esquema do banco de dados...\n";

    // 1. Fix import_batches
    // Check if status column exists
    $stmt = $db->query("SHOW COLUMNS FROM import_batches LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        echo "Adicionando coluna 'status' em import_batches...\n";
        $db->exec("ALTER TABLE import_batches ADD COLUMN status VARCHAR(50) DEFAULT 'success'");
    } else {
        echo "Coluna 'status' já existe em import_batches.\n";
    }

    // 2. Fix spc_excluidos
    // Check if vencimento column exists
    $stmt = $db->query("SHOW COLUMNS FROM spc_excluidos LIKE 'vencimento'");
    if ($stmt->rowCount() == 0) {
        echo "Adicionando coluna 'vencimento' em spc_excluidos...\n";
        $db->exec("ALTER TABLE spc_excluidos ADD COLUMN vencimento DATE AFTER contrato");
        $db->exec("ALTER TABLE spc_excluidos ADD INDEX (vencimento)");
    } else {
        echo "Coluna 'vencimento' já existe em spc_excluidos.\n";
    }

    echo "Reparo concluído!\n";

} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage();
}
