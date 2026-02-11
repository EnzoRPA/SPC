<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__ . '/arquivos Upados/INCLUIR - 26-11-2025.xlsx';

if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

echo "Loading file: $filePath\n";

try {
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, false, false);
    
    $header = $rows[0];
    print_r($header);
    
    $idxValor = -1;
    
    foreach ($header as $i => $colName) {
        $colName = mb_strtoupper(trim($colName), 'UTF-8');
        echo "Checking column $i: '$colName'\n";
        
        if (strpos($colName, 'VALOR') !== false) {
            echo "MATCH FOUND for VALOR at index $i\n";
            $idxValor = $i;
        }
    }
    
    if ($idxValor === -1) {
        echo "VALOR column NOT found.\n";
    } else {
        echo "VALOR column found at index: $idxValor\n";
        
        // Check first few values
        echo "Sample values:\n";
        for ($j = 1; $j <= 5; $j++) {
            if (isset($rows[$j])) {
                $val = $rows[$j][$idxValor];
                echo "Row $j: " . var_export($val, true) . "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
