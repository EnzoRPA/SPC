<?php
require 'config/db.php';
use App\Helpers\Normalizer;

$database = new Database();
$db = $database->getConnection();

echo "=== ANÁLISE DE CONFLITO: PDD PERDAS vs PDD PAGOS ===\n\n";

$totalPerdas = $db->query("SELECT COUNT(*) FROM pdd_perdas")->fetchColumn();
echo "Total PDD Perdas: $totalPerdas\n";

$totalPagos = $db->query("SELECT COUNT(*) FROM pdd_pagos")->fetchColumn();
echo "Total PDD Pagos: $totalPagos\n\n";

$sqlOverlapTotal = "SELECT COUNT(*) FROM pdd_perdas pp
                    WHERE EXISTS (
                        SELECT 1 FROM pdd_pagos pg 
                        WHERE pg.codigo_norm = pp.codigo_contrato_norm 
                           OR pg.titulo_norm = pp.codigo_contrato_norm
                    )";
$overlapTotal = $db->query($sqlOverlapTotal)->fetchColumn();

echo "Itens de PERDAS que também estão em PAGOS: $overlapTotal (Esses estão corretos em serem excluídos)\n";
echo "Itens de PERDAS que NÃO estão em PAGOS: " . ($totalPerdas - $overlapTotal) . " (POTENCIAIS DÍVIDAS ATIVAS)\n";

echo "\n--- Análise dos 'POTENCIAIS DÍVIDAS ATIVAS' ---\n";
// Let's look at those who are NOT in Pagos. Are they in Parcelas em Aberto?
// We need to isolate them first.
$missingFromDebt = $db->query("
    SELECT COUNT(*) 
    FROM pdd_perdas pp
    WHERE NOT EXISTS (
        SELECT 1 FROM pdd_pagos pg 
        WHERE pg.codigo_norm = pp.codigo_contrato_norm 
           OR pg.titulo_norm = pp.codigo_contrato_norm
    )
    AND NOT EXISTS (
        SELECT 1 FROM parcelas_em_aberto pa
        WHERE pa.contrato_norm = pp.codigo_contrato_norm
    )
")->fetchColumn();

echo "Desses potenciais, quantos NÃO estão em 'Parcelas em Aberto'? $missingFromDebt\n";
echo "Esses $missingFromDebt itens são os que o senhor quer que apareçam, mas o sistema não conhece mais.\n";
