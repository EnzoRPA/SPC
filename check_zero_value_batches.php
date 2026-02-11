<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$sql = "
    SELECT 
        b.id as batch_id, 
        b.imported_at, 
        COUNT(p.id) as zero_count 
    FROM pdd_perdas p
    JOIN import_batches b ON p.batch_id = b.id
    WHERE p.valor = 0
    GROUP BY b.id, b.imported_at
    ORDER BY b.imported_at DESC
";

$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = "Batches with zero values:\n";
foreach ($rows as $row) {
    $output .= "Batch ID: {$row['batch_id']} | Date: {$row['imported_at']} | Zero Count: {$row['zero_count']}\n";
}
file_put_contents('zero_batches_report.txt', $output);
echo "Report generated.\n";
