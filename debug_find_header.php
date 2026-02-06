<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'public/uploads/RELATORIO PDD - MAI-20251.xls';

if (!file_exists($file)) {
    die("Arquivo não encontrado.");
}

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);
$sheet = $spreadsheet->getActiveSheet();

echo "Scanning for header 'CONTRATO'...\n";

foreach ($sheet->getRowIterator() as $row) {
    if ($row->getRowIndex() > 10) break;
    
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    
    $cells = [];
    $found = false;
    foreach ($cellIterator as $cell) {
        $val = $cell->getValue();
        $cells[] = $val;
        if (is_string($val) && stripos($val, 'CONTRATO') !== false) {
            $found = true;
        }
    }
    
    if ($found) {
        echo "HEADER FOUND at Row " . $row->getRowIndex() . ":\n";
        echo implode(" | ", $cells) . "\n";
        
        // Print indices
        foreach ($cells as $i => $v) {
            if ($v) echo "[$i] $v\n";
        }
        exit;
    }
}
echo "Header not found in first 10 rows.\n";
