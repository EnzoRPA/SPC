<?php
require_once 'config/db.php';
require_once 'src/AdminController.php';

$database = new Database();
$db = $database->getConnection();
$admin = new \App\AdminController($db);

// 1. Insert dummy record
$db->exec("INSERT INTO spc_inclusos (contrato, contratante, cpf_cnpj, debito, vencimento, data_inclusao) VALUES ('TEST-ARCHIVE', 'Test User', '000.000.000-00', 100.00, '2025-01-01', '2025-01-01')");
$id = $db->lastInsertId();

echo "Inserted dummy record ID: $id\n";

// 2. Delete it using AdminController
echo "Attempting to delete record ID: $id...\n";
try {
    $result = $admin->delete('spc_inclusos', $id);
    echo "Delete result: " . ($result ? 'TRUE' : 'FALSE') . "\n";
} catch (Exception $e) {
    echo "Delete Exception: " . $e->getMessage() . "\n";
}

// 3. Check history
$stmt = $db->prepare("SELECT * FROM spc_historico_removidos WHERE original_id = ?");
$stmt->execute([$id]);
$history = $stmt->fetch(PDO::FETCH_ASSOC);

if ($history) {
    echo "SUCCESS: Record found in history!\n";
    print_r($history);
} else {
    echo "FAILURE: Record NOT found in history.\n";
}

// 4. Check for error log
if (file_exists('debug_archive_error.log')) {
    echo "Error Log Found:\n";
    echo file_get_contents('debug_archive_error.log');
} else {
    echo "No error log found.\n";
}
