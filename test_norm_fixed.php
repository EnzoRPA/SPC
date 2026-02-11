<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

echo "=== TESTE DE NORMALIZAÇÃO ATUALIZADA ===\n\n";

$tests = [
    ['1987', '1987'],
    ['1987 P1', '1987P1'],  // Esperado: ambos viram 1987P1
    ['1987P1', '1987P1'],
    ['0001987', '1987'],
    ['  1987  ', '1987'],
];

$allPass = true;

foreach ($tests as $test) {
    $input = $test[0];
    $expected = $test[1];
    $result = Normalizer::normalizarContrato($input);
    $pass = ($result === $expected);
    
    $status = $pass ? "✓" : "✗";
    echo "$status Input: '$input' -> '$result' " . ($pass ? "" : "(esperado: '$expected')") . "\n";
    
    if (!$pass) $allPass = false;
}

echo "\n";

if ($allPass) {
    echo "✅ TODOS OS TESTES PASSARAM!\n";
    echo "\nAgora '1987 P1' será normalizado para '1987P1',\n";
    echo "MAS o banco tem '1987' que normaliza para '1987'...\n";
    echo "\n⚠ ATENÇÃO: As normalizações ainda são DIFERENTES!\n";
    echo "   Planilha '1987 P1' -> '1987P1'\n";
    echo "   Banco '1987' -> '1987'\n";
    echo "\nSOLUÇÃO: Na planilha, use apenas '1987' (sem P1)\n";
} else {
    echo "⚠ Alguns testes falharam!\n";
}
