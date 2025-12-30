<?php
require 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$output = "=== DEBUGGING DATA TRUNCATION ===\n\n";

// 1. Check import_batches schema
$output .= "1. Checking import_batches schema:\n";
$stmt = $db->query("DESCRIBE import_batches");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    if ($col['Field'] === 'type') {
        $output .= "   - Column 'type': " . $col['Type'] . "\n";
    }
}

// 2. Test insert into import_batches
$output .= "\n2. Testing insert into import_batches:\n";
try {
    $stmt = $db->prepare("INSERT INTO import_batches (filename, type) VALUES (?, ?)");
    $stmt->execute(['test_file.xlsx', 'pdd_perdas']);
    $output .= "   - Insert 'pdd_perdas' SUCCESS\n";
} catch (Exception $e) {
    $output .= "   - Insert 'pdd_perdas' FAILED: " . $e->getMessage() . "\n";
}

// 3. Check pdd_pagos schema
$output .= "\n3. Checking pdd_pagos schema:\n";
$stmt = $db->query("DESCRIBE pdd_pagos");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    $output .= "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

// 4. Check pdd_perdas schema
$output .= "\n4. Checking pdd_perdas schema:\n";
$stmt = $db->query("DESCRIBE pdd_perdas");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    $output .= "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

file_put_contents('debug_log.txt', $output);
echo "Debug log written to debug_log.txt";

