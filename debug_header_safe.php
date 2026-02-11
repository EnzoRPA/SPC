<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if ($row >= $this->startRow && $row < $this->endRow) {
            return true;
        }
        return false;
    }
}

$filePath = __DIR__ . '/arquivos Upados/INCLUIR - 26-11-2025.xlsx';
echo "File: $filePath\n";

$reader = IOFactory::createReaderForFile($filePath);
$filter = new ChunkReadFilter();
$filter->setRows(1, 100); 
$reader->setReadFilter($filter);
$reader->setReadDataOnly(true); // Faster, less memory

$spreadsheet = $reader->load($filePath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, false, false);

echo "Loaded " . count($rows) . " rows.\n";

if (count($rows) > 0) {
    $header = $rows[0];
    echo "Header: " . implode(" | ", $header) . "\n";
    
    $idxValor = -1;
    foreach ($header as $i => $colName) {
        $colName = mb_strtoupper(trim($colName), 'UTF-8');
        if ((strpos($colName, 'VALOR') !== false) || (strpos($colName, 'VLR') !== false) || (strpos($colName, 'SALDO') !== false) || (strpos($colName, 'MONTANTE') !== false)) {
            if ($idxValor === -1) $idxValor = $i;
        }
    }
    echo "Valor Index: $idxValor\n";
    
    if ($idxValor !== -1) {
        // Values in next rows
        for ($i = 1; $i < min(10, count($rows)); $i++) {
             $val = $rows[$i][$idxValor];
             echo "Row " . ($i+1) . ": " . var_export($val, true) . "\n";
        }
    }
}
