<?php
require_once 'src/Helpers/Normalizer.php';
use App\Helpers\Normalizer;

$dates = [
    '0025-09-20',
    '0024-06-22',
    '2025-09-20',
    '20/09/0025',
    '20/09/25'
];

echo "=== Teste de Normalização de Datas ===\n";
foreach ($dates as $date) {
    $normalized = Normalizer::data($date);
    echo "Input: '$date' -> Output: '$normalized'\n";
}
