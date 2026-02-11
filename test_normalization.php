<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

echo "=== TESTE DE NORMALIZAÇÃO ===\n\n";

$contratos = [
    '1987',
    '1987 P1',
    '1987P1',
    'Deposito P3/31172021',
];

echo "Testando normalização de contratos:\n\n";
foreach ($contratos as $c) {
    $norm = Normalizer::normalizarContrato($c);
    echo "Original: '$c'\n";
    echo "Normalizado: '$norm'\n\n";
}

// Testar detecção de coluna com /
$colunas = [
    'CPF',
    'CPF_CNPJ',
    'CPF_CNPJ/CPL_CONTRATANTE',
    'CPF/CNPJ',
];

echo "\nTestando detecção de colunas de CPF:\n\n";
foreach ($colunas as $col) {
    $colUpper = strtoupper($col);
    $detected = (strpos($colUpper, 'CPF') !== false || strpos($colUpper, 'CNPJ') !== false);
    echo "Coluna: '$col'\n";
    echo "Detectada: " . ($detected ? "SIM ✓" : "NÃO ✗") . "\n\n";
}
