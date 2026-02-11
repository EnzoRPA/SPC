<?php
/**
 * DEBUG PROFUNDO: Adicionar logs EXTREMOS ao DataEnrichmentImporter
 */

echo "=== ADICIONANDO LOGS DE DEBUG ===\n\n";

$file = __DIR__ . '/src/Importers/DataEnrichmentImporter.php';
$code = file_get_contents($file);

// Verificar se já tem os logs de debug
if (strpos($code, 'DEBUG LOGGING: Show what columns were detected') !== false) {
    echo "✓ Logs de detecção de colunas JÁ existem\n";
} else {
    echo "✗ Logs de detecção de colunas NÃO encontrados!\n";
}

if (strpos($code, 'Updated $updatedCountPdd records in pdd_perdas') !== false) {
    echo "✓ Logs de resultado JÁ existem\n";
} else {
    echo "✗ Logs de resultado NÃO encontrados!\n";
}

// Verificar estrutura do UPDATE
if (strpos($code, 'WHERE codigo_contrato_norm = ?') !== false) {
    echo "✓ UPDATE em pdd_perdas usa codigo_contrato_norm\n";
} else {
    echo "✗ UPDATE em pdd_perdas NÃO encontrado!\n";
}

if (strpos($code, 'WHERE contrato_norm = ?') !== false) {
    echo "✓ UPDATE em spc_inclusos usa contrato_norm\n";
} else {
    echo "✗ UPDATE em spc_inclusos NÃO encontrado!\n";
}

echo "\n=== VERIFICAÇÃO CRÍTICA ===\n";
echo "Vou adicionar LOG EXTRA para ver CADA linha processada\n\n";

// Encontrar a linha onde está o loop de processamento
$lines = file($file);
$modified = false;

foreach ($lines as $i => $line) {
    // Procurar pelo ponto onde processamos cada linha de dados
    if (strpos($line, '$contratoNorm = Normalizer::contrato($contrato);') !== false) {
        // Adicionar log logo após normalização
        $indent = str_repeat(' ', 12);
        $newLog = $indent . "error_log(\"DEBUG: Processando contrato '$contrato' -> norm: '$contratoNorm'\");\n";
        
        // Verificar se já existe
        if (!isset($lines[$i+1]) || strpos($lines[$i+1], 'DEBUG: Processando contrato') === false) {
            array_splice($lines, $i+1, 0, $newLog);
            $modified = true;
            echo "✓ Adicionado log de processamento de contrato\n";
            break;
        }
    }
}

if ($modified) {
    file_put_contents($file, implode('', $lines));
    echo "\n✅ Arquivo modificado com logs extras!\n";
} else {
    echo "\n⚠ Não foi necessário modificar (logs já existem ou estrutura mudou)\n";
}

echo "\nPróximo passo: Importe a planilha novamente e veja os logs do PHP\n";
