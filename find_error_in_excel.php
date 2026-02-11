<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$uploadDir = __DIR__ . '/public/uploads';
$files = glob($uploadDir . '/*.xlsx');

if ($files === false) {
    echo "Error: glob returned false.\n";
    exit(1);
}

// Sort by modified time descending
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Take top 5
$files = array_slice($files, 0, 5);

echo "Scanning " . count($files) . " most recent files in public/uploads...\n";

foreach ($files as $file) {
    echo "Checking file: " . $file . "\n";
    
    if (!file_exists($file)) {
        echo "  [ERROR] File does not exist.\n";
        continue;
    }

    try {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true); // Optimization
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Use row iterator to save memory
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue();
                if ($cellValue === null) continue;
                
                // Check for the specific year causing the error '215437'
                if (strpos((string)$cellValue, '215437') !== false) {
                    echo "  [FOUND] Value '$cellValue' found at Row " . $row->getRowIndex() . ", Column " . $cell->getColumn() . "\n";
                }
                
                 if (preg_match('/(\d{4})/', (string)$cellValue, $matches)) {
                    $year = (int)$matches[1];
                     if ($year > 2100 && $year < 3000) {
                         if (strpos((string)$cellValue, '/') !== false || strpos((string)$cellValue, '-') !== false) {
                            echo "  [SUSPICIOUS] Value '$cellValue' (Year $year) found at Row " . $row->getRowIndex() . ", Column " . $cell->getColumn() . "\n";
                         }
                     }
                 }
                 // Check if it's a numeric date that translates to > 2100
                 if (is_numeric($cellValue) && $cellValue > 47000) { // 47000 is approx year 2028
                      // 215437 is definitely > 47000
                      if ($cellValue >= 215430 && $cellValue <= 215440) {
                          echo "  [FOUND] Numeric Value '$cellValue' found at Row " . $row->getRowIndex() . ", Column " . $cell->getColumn() . "\n";
                      }
                 }
            }
        }
        // Free memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    } catch (\Throwable $e) {
        echo "  [ERROR] Could not read file: " . $e->getMessage() . "\n";
        echo "  [DEBUG] File path length: " . strlen($file) . "\n";
    }
}
echo "Done.\n";
