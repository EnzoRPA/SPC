<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE spc_inclusos");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns in spc_inclusos:\n";
print_r($columns);
