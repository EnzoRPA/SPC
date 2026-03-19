<?php
/**
 * Find ALL spc_inclusos records blocked from exclusion by spc_historico_removidos
 * Fixed GROUP BY for strict SQL mode
 */
require_once __DIR__ . '/config/db.php';
$dbObj = new Database();
$db = $dbObj->getConnection();

ini_set('memory_limit', '1G');

echo "=== BUSCA DE TODOS OS CASOS BLOQUEADOS POR HISTORICO ===\n\n";

// Step 1: Count
$sql1 = "SELECT COUNT(*) as total FROM spc_inclusos s
    LEFT JOIN parcelas_em_aberto p ON s.cpf_cnpj_norm = p.cpf_cnpj_norm AND s.contrato_norm = p.contrato_norm
    LEFT JOIN pdd_perdas pp ON s.contrato_norm = pp.codigo_contrato_norm
    WHERE p.id IS NULL AND pp.id IS NULL";
$count1 = $db->query($sql1)->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total spc_inclusos sem parcela e sem PDD: $count1\n";

$sql2 = "SELECT COUNT(DISTINCT s.id) as total FROM spc_inclusos s
    LEFT JOIN parcelas_em_aberto p ON s.cpf_cnpj_norm = p.cpf_cnpj_norm AND s.contrato_norm = p.contrato_norm
    LEFT JOIN pdd_perdas pp ON s.contrato_norm = pp.codigo_contrato_norm
    INNER JOIN spc_historico_removidos ex ON s.contrato = ex.contrato AND (ex.vencimento IS NULL OR s.vencimento = ex.vencimento)
    WHERE p.id IS NULL AND pp.id IS NULL";
$count2 = $db->query($sql2)->fetch(PDO::FETCH_ASSOC)['total'];
echo "Destes, BLOQUEADOS pelo historico: $count2\n\n";

// Step 2: Get sample details (first 20)
$sql3 = "SELECT s.id as spc_id, s.contrato, s.contratante, s.cpf_cnpj, s.debito, s.vencimento,
           MIN(ex.id) as hist_id, MIN(ex.motivo_remocao) as motivo_remocao
    FROM spc_inclusos s
    LEFT JOIN parcelas_em_aberto p ON s.cpf_cnpj_norm = p.cpf_cnpj_norm AND s.contrato_norm = p.contrato_norm
    LEFT JOIN pdd_perdas pp ON s.contrato_norm = pp.codigo_contrato_norm
    INNER JOIN spc_historico_removidos ex ON s.contrato = ex.contrato AND (ex.vencimento IS NULL OR s.vencimento = ex.vencimento)
    WHERE p.id IS NULL AND pp.id IS NULL
    GROUP BY s.id, s.contrato, s.contratante, s.cpf_cnpj, s.debito, s.vencimento
    ORDER BY s.contrato
    LIMIT 20";

$stmt = $db->query($sql3);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- AMOSTRA (20 primeiros) ---\n";
foreach ($results as $r) {
    echo "  SPC_ID={$r['spc_id']}, contrato={$r['contrato']}, contratante={$r['contratante']}, debito={$r['debito']}, vencimento={$r['vencimento']}, bloqueado_por_hist={$r['hist_id']}, motivo='{$r['motivo_remocao']}'\n";
}

// Step 3: Collect ALL blocking hist IDs
$sql4 = "SELECT DISTINCT ex.id as hist_id FROM spc_inclusos s
    LEFT JOIN parcelas_em_aberto p ON s.cpf_cnpj_norm = p.cpf_cnpj_norm AND s.contrato_norm = p.contrato_norm
    LEFT JOIN pdd_perdas pp ON s.contrato_norm = pp.codigo_contrato_norm
    INNER JOIN spc_historico_removidos ex ON s.contrato = ex.contrato AND (ex.vencimento IS NULL OR s.vencimento = ex.vencimento)
    WHERE p.id IS NULL AND pp.id IS NULL";
$allHistIds = $db->query($sql4)->fetchAll(PDO::FETCH_COLUMN);

echo "\n--- RESUMO ---\n";
echo "Total SPC Inclusos bloqueados: $count2 de $count1\n";
echo "Total IDs historico bloqueadores: " . count($allHistIds) . "\n";

// Step 4: Breakdown by motivo
$sql5 = "SELECT ex.motivo_remocao, COUNT(DISTINCT s.id) as total FROM spc_inclusos s
    LEFT JOIN parcelas_em_aberto p ON s.cpf_cnpj_norm = p.cpf_cnpj_norm AND s.contrato_norm = p.contrato_norm
    LEFT JOIN pdd_perdas pp ON s.contrato_norm = pp.codigo_contrato_norm
    INNER JOIN spc_historico_removidos ex ON s.contrato = ex.contrato AND (ex.vencimento IS NULL OR s.vencimento = ex.vencimento)
    WHERE p.id IS NULL AND pp.id IS NULL
    GROUP BY ex.motivo_remocao
    ORDER BY total DESC";
$breakdown = $db->query($sql5)->fetchAll(PDO::FETCH_ASSOC);
echo "\nBreakdown por motivo de remocao:\n";
foreach ($breakdown as $b) {
    echo "  {$b['motivo_remocao']}: {$b['total']}\n";
}

echo "\n=== FIM ===\n";
