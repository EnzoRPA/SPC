<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Pega o arquivo mais recente
$files = glob('uploads/*.xlsx');
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
$file = $files[0] ?? null;

if (!$file) {
    die("Nenhum arquivo encontrado.");
}

echo "Lendo arquivo: $file<br><br>";

try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();

    echo "=== Primeiras 5 linhas ===<br>";
    foreach ($sheet->getRowIterator() as $row) {
        if ($row->getRowIndex() > 5) break;
        
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        
        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue();
        }
        
        echo "Linha " . $row->getRowIndex() . ": " . implode(" | ", $cells) . "<br>";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
