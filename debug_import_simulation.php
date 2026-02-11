<?php
require 'vendor/autoload.php';
require 'config/db.php';
require 'src/Helpers/Normalizer.php';
// We don't need the full importer class if we just copy the logic for testing
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Helpers\Normalizer;

$filePath = __DIR__ . '/arquivos Upados/INCLUIR - 26-11-2025.xlsx';

echo "Loading $filePath...\n";
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, false, false); // Raw values

$header = array_shift($rows);
echo "Headers: " . implode(" | ", $header) . "\n";

$idxValor = -1;
foreach ($header as $i => $colName) {
    $colName = mb_strtoupper(trim($colName), 'UTF-8');
    if ((strpos($colName, 'VALOR') !== false) || (strpos($colName, 'VLR') !== false) || (strpos($colName, 'SALDO') !== false) || (strpos($colName, 'MONTANTE') !== false)) {
        if ($idxValor === -1) $idxValor = $i;
    }
}

echo "Valor Index: $idxValor\n";

if ($idxValor !== -1) {
    echo "Checking first 5 rows:\n";
    foreach ($rows as $index => $row) {
        if ($index >= 5) break;
        
        $valorRaw = $row[$idxValor];
        $valorNorm = Normalizer::valor($valorRaw);
        
        echo "Row $index: Raw=" . var_export($valorRaw, true) . " -> Norm=$valorNorm\n";
    }
} else {
    echo "VALOR column not found with new logic!\n";
}
