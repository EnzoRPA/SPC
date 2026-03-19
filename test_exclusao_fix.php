<?php
/**
 * Test: verify the exclusion query now returns results after removing historico blocker
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

$dbObj = new Database();
$db = $dbObj->getConnection();

require_once __DIR__ . '/src/Comparator.php';
$comparator = new App\Comparator($db);

echo "=== TESTE DA QUERY DE EXCLUSAO APOS CORRECAO ===\n\n";

$resultados = $comparator->obterParaExclusao();
echo "Total de registros para exclusao: " . count($resultados) . "\n\n";

if (count($resultados) > 0) {
    // Check if 47661 is in the results
    $found47661 = false;
    foreach ($resultados as $r) {
        if ($r['contrato'] == '47661') {
            $found47661 = true;
            echo "*** CONTRATO 47661 ENCONTRADO! ***\n";
            echo "  contrato={$r['contrato']}, contratante={$r['contratante']}, debito={$r['debito']}, vencimento={$r['vencimento']}, motivo={$r['motivo']}\n\n";
        }
    }
    if (!$found47661) {
        echo "*** CONTRATO 47661 NAO ENCONTRADO nos resultados ***\n\n";
    }
    
    // Show first 10 results as sample
    echo "--- AMOSTRA (10 primeiros) ---\n";
    $count = 0;
    foreach ($resultados as $r) {
        if ($count >= 10) break;
        echo "  contrato={$r['contrato']}, contratante={$r['contratante']}, debito={$r['debito']}, motivo={$r['motivo']}\n";
        $count++;
    }
}

echo "\n=== FIM ===\n";
