<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Shared\Date;

try {
    $date = Date::excelToDateTimeObject(45767);
    echo "Date: " . $date->format('Y-m-d') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
