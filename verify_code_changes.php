<?php
/**
 * Simple test to verify DataEnrichmentImporter code changes
 */

echo "=== TESTE RÁPIDO: Verificação de Código ===\n\n";

$code = file_get_contents(__DIR__ . '/src/Importers/DataEnrichmentImporter.php');

echo "Verificando alterações no código:\n\n";

$checks = [
    '1. Memory limit 1GB' => strpos($code, "ini_set('memory_limit', '1024M')") !== false,
    '2. Skip empty cells' => strpos($code, 'setReadEmptyCells(false)') !== false,
    '3. Update SPC rua' => strpos($code, 'rua = COALESCE(?, rua)') !== false,
    '4. Update SPC numero' => strpos($code, 'numero = COALESCE(?, numero)') !== false,
    '5. Update SPC bairro' => strpos($code, 'bairro = COALESCE(?, bairro)') !== false,
    '6. Update SPC cidade' => strpos($code, 'cidade = COALESCE(?, cidade)') !== false,
    '7. Update SPC estado' => strpos($code, 'estado = COALESCE(?, estado)') !== false,
    '8. Update SPC cep' => strpos($code, 'cep = COALESCE(?, cep)') !== false,
    '9. Flexible column detection (colNameClean)' => strpos($code, 'colNameClean') !== false,
    '10. Remove accents (str_replace)' => strpos($code, 'str_replace') !== false && strpos($code, 'Ã') !== false,
    '11. Debug logging' => strpos($code, 'Columns detected:') !== false,
    '12. Extract rua component' => strpos($code, '$rua = ($idxRua !== -1 && isset($rowData[$idxRua]))') !== false,
    '13. Extract numero component' => strpos($code, '$numero = ($idxNumero !== -1 && isset($rowData[$idxNumero]))') !== false,
    '14. Execute SPC update with address' => strpos($code, '$stmtUpdateSpc->execute([') !== false,
];

$allPassed = true;
foreach ($checks as $check => $passed) {
    $status = $passed ? "✓ PASS" : "✗ FAIL";
    echo "  $status - $check\n";
    if (!$passed) $allPassed = false;
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($allPassed) {
    echo "✅ SUCESSO! Todas as 14 alterações estão presentes!\n";
    echo "\nO código foi atualizado corretamente com:\n";
    echo "  • Otimização de memória (1GB limit)\n";
    echo "  • UPDATE completo de endereço em spc_inclusos\n";
    echo "  • Detecção flexível de colunas\n";
    echo "  • Logging de debug\n";
    echo "\nPróximo passo:\n";
    echo "  1. Reinicie o servidor web/PHP (se estiver rodando)\n";
    echo "  2. Importe uma planilha via item 6\n";
    echo "  3. Verifique os logs do PHP para ver 'Columns detected:'\n";
} else {
    echo "⚠ ATENÇÃO! Algumas alterações NÃO foram encontradas!\n";
    echo "\nPossíveis causas:\n";
    echo "  1. Arquivo não foi salvo\n";
    echo "  2. Editando arquivo errado (dev vs prod)\n";
    echo "  3. Cache do opcache PHP\n";
}

echo "\n";
