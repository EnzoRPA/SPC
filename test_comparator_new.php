<?php
require 'config/db.php';
require 'src/Comparator.php';
use App\Comparator;

$database = new Database();
$db = $database->getConnection();

$comp = new Comparator($db);

echo "=== TESTING NEW COMPARATOR LOGIC ===\n\n";

// Test Inclusion
echo "--- Obter Para Inclusao ---\n";
$inclusao = $comp->obterParaInclusao();
echo "Total items for inclusion: " . count($inclusao) . "\n";

$pddCount = 0;
$sampleShown = false;

foreach ($inclusao as $item) {
    if (strpos($item['motivo'], 'PDD') !== false) {
        $pddCount++;
        if (!$sampleShown) {
            echo "Sample PDD Item:\n";
            print_r($item);
            $sampleShown = true;
        }
    }
}
echo "PDD Items found: $pddCount\n";

// Test Exclusion (Protection Check)
// We can't really test protection easily without setting up a scenario where an item is IN spc_inclusos but OUT of Open.
// But we can check if any current SPC items are flagged for removal inappropriately.
echo "\n--- Obter Para Exclusao ---\n";
$exclusao = $comp->obterParaExclusao();
echo "Total items for exclusion: " . count($exclusao) . "\n";
// Check if any have PDD in motivo (legacy PDD PAGO check)
$pddPagoCount = 0;
foreach ($exclusao as $item) {
    if ($item['motivo'] === 'PDD PAGO' || $item['motivo'] === 'PDD PAGO (Titulo)') {
        $pddPagoCount++;
    }
}
echo "Items marked as PDD PAGO: $pddPagoCount\n";
