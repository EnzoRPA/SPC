<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'public/uploads/pdd geral fev-2025 para tras.xlsx';

if (!file_exists($file)) {
    die("Arquivo não encontrado: $file");
}

echo "Lendo header de: $file\n";

try {
    $reader = IOFactory::createReaderForFile($file);
    $reader->setReadDataOnly(true);
    // Limit to top 5 rows
    $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
        public function readCell($column, $row, $worksheetName = '') {
            return $row <= 5;
        }
    });

    $spreadsheet = $reader->load($file);
    $sheet = $spreadsheet->getActiveSheet();
    
    // FETCH EXPLICITLY
    for ($r = 1; $r <= 5; $r++) {
        $cells = [];
        for ($c = 1; $c <= 20; $c++) { // Check first 20 cols
             $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
             $cells[] = $sheet->getCell($colLetter . $r)->getValue();
        }
        echo "Row $r: " . implode(" | ", $cells) . "\n";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
