<?php
require 'vendor/autoload.php';
require 'config/db.php';
require 'src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

echo "=== TESTING NORMALIZER::VALOR ===\n";

$testCases = [
    '1200' => 1200.00,
    '1200.00' => 1200.00,
    '1200,00' => 1200.00,
    '1.200,00' => 1200.00,
    '1,200.00' => 1200.00,
    'R$ 1.200,00' => 1200.00,
    'R$1.200,00' => 1200.00,
    ' R$ 1.200,00 ' => 1200.00,
    "1\xA0200,00" => 1200.00, // Non-breaking space
    null => 0.0,
    '' => 0.0
];

foreach ($testCases as $input => $expected) {
    $result = Normalizer::valor($input);
    $status = (abs($result - $expected) < 0.001) ? "PASS" : "FAIL";
    echo "Input: " . var_export($input, true) . " -> Expected: $expected -> Got: $result [$status]\n";
}

echo "\n=== TESTING HEADER LOGIC (Simulation) ===\n";

// Mocking the header detection logic from PddPerdasImporter
function findIndices($header) {
    $idxValor = -1;
    echo "Headers: " . implode(", ", $header) . "\n";
    foreach ($header as $i => $colName) {
        $colName = mb_strtoupper(trim($colName), 'UTF-8');
        if ((strpos($colName, 'VALOR') !== false) || (strpos($colName, 'VLR') !== false) || (strpos($colName, 'SALDO') !== false) || (strpos($colName, 'MONTANTE') !== false)) {
            if ($idxValor === -1) $idxValor = $i;
        }
    }
    echo "Valor Index: $idxValor\n";
    return $idxValor;
}

findIndices(['Data', 'Contrato', 'Valor']);
findIndices(['Data', 'Contrato', 'Vlr']);
findIndices(['Data', 'Contrato', 'Saldo Devedor']);
findIndices(['Data', 'Contrato', 'Montante']);
findIndices(['Data', 'Contrato', 'Outro']);

