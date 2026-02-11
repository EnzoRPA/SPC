<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__ . '/arquivos Upados/INCLUIR - 26-11-2025.xlsx';

if (!file_exists($filePath)) {
    die("File not found\n");
}

echo "Loading...\n";
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getActiveSheet();
echo "Loaded.\n";

$val = $sheet->getCell('A1')->getValue();
echo "A1: " . var_export($val, true) . "\n";

$rows = $sheet->toArray(null, true, false, false);
echo "Rows count: " . count($rows) . "\n";
if (count($rows) > 0) {
    echo "First row (header):\n";
    foreach ($rows[0] as $k => $v) {
        echo "[$k] (" . gettype($v) . "): " . (is_scalar($v) ? $v : 'OBJECT') . "\n";
    }
}
