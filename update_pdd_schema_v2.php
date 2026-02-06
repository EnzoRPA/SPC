<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "=== UPDATING PDD_PERDAS SCHEMA FOR CPF/ADDRESS ===\n";

try {
    $db->exec("ALTER TABLE pdd_perdas ADD COLUMN cpf_cnpj VARCHAR(20) NULL AFTER nome");
    echo "Added 'cpf_cnpj' column.\n";
} catch (PDOException $e) {
    echo "cpf_cnpj column might already exist.\n";
}

try {
    $db->exec("ALTER TABLE pdd_perdas ADD COLUMN endereco VARCHAR(255) NULL AFTER valor");
    echo "Added 'endereco' column.\n";
} catch (PDOException $e) {
    echo "endereco column might already exist.\n";
}

$stmt = $db->query("DESCRIBE pdd_perdas");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "Column: {$col['Field']} ({$col['Type']})\n";
}
