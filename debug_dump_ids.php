<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== DUMPING ID 73 (SPC Inclusos) ===\n";
$stmt = $db->query("SELECT * FROM spc_inclusos WHERE id = 73");
$r73 = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($r73);

echo "\n=== DUMPING ID 746 (Parcelas Em Aberto) ===\n";
$stmt = $db->query("SELECT * FROM parcelas_em_aberto WHERE id = 746");
$r746 = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($r746);
