<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();
echo 'Parcelas: ' . $db->query('SELECT COUNT(*) FROM parcelas_em_aberto')->fetchColumn() . PHP_EOL;
echo 'Inclusos: ' . $db->query('SELECT COUNT(*) FROM spc_inclusos')->fetchColumn() . PHP_EOL;
