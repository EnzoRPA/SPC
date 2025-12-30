<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Pega o arquivo mais recente ou um específico
$file = 'public/uploads/parcelas_em_aberto (22).xlsx';

if (!file_exists($file)) {
    // Tenta achar o mais recente se o nome fixo não existir
    $files = glob('public/uploads/*.xlsx');
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $file = $files[0] ?? null;
}

if (!$file) {
    die("Nenhum arquivo encontrado.");
}

echo "Lendo arquivo: $file\n\n";

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

echo "=== Primeiras 5 linhas ===\n";
$rows = [];
foreach ($sheet->getRowIterator() as $row) {
    if ($row->getRowIndex() > 5) break;
    
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    
    $cells = [];
    foreach ($cellIterator as $cell) {
        $cells[] = $cell->getValue();
    }
    $rows[] = $cells;
    
    echo "Linha " . $row->getRowIndex() . ": " . implode(" | ", $cells) . "\n";
}
