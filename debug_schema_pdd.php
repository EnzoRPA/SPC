<?php
require 'config/db.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE pdd_perdas");
echo implode(", ", $stmt->fetchAll(PDO::FETCH_COLUMN));
