<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Creating spc_ignorados table ===\n";

$sql = "CREATE TABLE IF NOT EXISTS spc_ignorados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_norm VARCHAR(255) NULL,
    cpf_cnpj_norm VARCHAR(255) NULL,
    motivo VARCHAR(255) NULL,
    data_ignorado DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (contrato_norm),
    INDEX (cpf_cnpj_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $db->exec($sql);
    echo "Table spc_ignorados created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
