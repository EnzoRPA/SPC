<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$tempFile = __DIR__ . '/temp_test_pdd.xlsx';
if (!file_exists($tempFile)) {
    die("File not found: $tempFile");
}

echo "Loading file...\n";
$spreadsheet = IOFactory::load($tempFile);
$sheet = $spreadsheet->getActiveSheet();
echo "File loaded. Calling toArray...\n";

try {
    $rows = $sheet->toArray(null, true, false, false);
    echo "toArray success. Row count: " . count($rows) . "\n";
    print_r($rows[0]); // Header
    print_r($rows[1]); // Data
} catch (Exception $e) {
    echo "Error in toArray: " . $e->getMessage() . "\n";
}
