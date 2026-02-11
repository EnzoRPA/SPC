<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$logFile = __DIR__ . '/scan_results.txt';
file_put_contents($logFile, "Starting scan...\n");

$uploadDir = __DIR__ . '/public/uploads';
// Get all xlsx files
$files = glob($uploadDir . '/*.xlsx');

if (empty($files)) {
    file_put_contents($logFile, "No files found.\n", FILE_APPEND);
    exit;
}

// Sort by modified time descending
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Take top 5
$files = array_slice($files, 0, 5);

foreach ($files as $file) {
    $filename = basename($file);
    file_put_contents($logFile, "Checking file: $filename\n", FILE_APPEND);
    
    try {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $val = $cell->getValue();
                if ($val === null) continue;
                
                // Check string match for 215437
                if (strpos((string)$val, '215437') !== false) {
                    $msg = "  [FOUND] Value '$val' at Row $rowIndex, Col " . $cell->getColumn() . "\n";
                    file_put_contents($logFile, $msg, FILE_APPEND);
                }
                
                // Check calculated date from numeric
                if (is_numeric($val) && $val > 45000) {
                     // Check if year is around 215437
                     // 1 year = 365.25
                     // 215437 is huge.
                     // Maybe it's not a date serial but a raw number?
                     if ($val >= 215430 && $val <= 215440) {
                         $msg = "  [FOUND NUMERIC] Value '$val' at Row $rowIndex, Col " . $cell->getColumn() . "\n";
                         file_put_contents($logFile, $msg, FILE_APPEND);
                     }
                }
            }
        }
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    } catch (\Throwable $e) {
        file_put_contents($logFile, "  Error reading $filename: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

file_put_contents($logFile, "Scan complete.\n", FILE_APPEND);
