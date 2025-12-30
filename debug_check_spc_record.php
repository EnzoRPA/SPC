<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$id = 4529;
echo "=== Detalhes do Registro SPC Inclusos ID: $id ===\n";

$stmt = $db->prepare("SELECT * FROM spc_inclusos WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($record) {
    print_r($record);
} else {
    echo "Registro não encontrado.\n";
}
