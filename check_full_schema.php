<?php
require_once __DIR__ . '/config/db.php';
$db = (new Database())->getConnection();

function dumpTable($db, $table) {
    echo "=== TABLE: $table ===\n";
    $stmt = $db->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
}

dumpTable($db, 'pdd_perdas');
dumpTable($db, 'spc_inclusos');
