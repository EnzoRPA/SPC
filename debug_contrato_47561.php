<?php
/**
 * Debug script to investigate why contract 47661 is not appearing in exclusion list
 */
require_once __DIR__ . '/config/db.php';
$dbObj = new Database();
$db = $dbObj->getConnection();

if (!$db) {
    die("Erro: Nao foi possivel conectar ao banco de dados.\n");
}

echo "=== DEBUG CONTRATO 47661 ===\n\n";
echo "Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n\n";

// 1. Check spc_inclusos with LIKE
echo "--- SPC INCLUSOS ---\n";
$stmt = $db->prepare("SELECT id, contrato, contrato_norm, cpf_cnpj, cpf_cnpj_norm, venda, parcela, debito, vencimento, data_inclusao FROM spc_inclusos WHERE contrato LIKE '%47661%' OR contrato_norm LIKE '%47661%'");
$stmt->execute();
$inclusos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($inclusos as $r) {
    echo "  ID={$r['id']}, contrato='{$r['contrato']}', contrato_norm='{$r['contrato_norm']}', cpf='{$r['cpf_cnpj']}', cpf_norm='{$r['cpf_cnpj_norm']}', venda='{$r['venda']}', parcela='{$r['parcela']}', debito={$r['debito']}, vencimento={$r['vencimento']}\n";
}
if (empty($inclusos)) echo "  NENHUM REGISTRO ENCONTRADO\n";

// 2. Check parcelas_em_aberto
echo "\n--- PARCELAS EM ABERTO ---\n";
$stmt = $db->prepare("SELECT id, contrato, contrato_norm, cpf_cnpj, cpf_cnpj_norm, venda, parcela, debito, vencimento FROM parcelas_em_aberto WHERE contrato LIKE '%47661%' OR contrato_norm LIKE '%47661%'");
$stmt->execute();
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($parcelas as $r) {
    echo "  ID={$r['id']}, contrato='{$r['contrato']}', contrato_norm='{$r['contrato_norm']}', cpf='{$r['cpf_cnpj']}', cpf_norm='{$r['cpf_cnpj_norm']}', venda='{$r['venda']}', parcela='{$r['parcela']}', debito={$r['debito']}, vencimento={$r['vencimento']}\n";
}
if (empty($parcelas)) echo "  NENHUM REGISTRO ENCONTRADO\n";

// 3. Check pdd_perdas
echo "\n--- PDD PERDAS ---\n";
$stmt = $db->prepare("SELECT id, codigo_contrato, codigo_contrato_norm, codigo_venda, cpf_cnpj, valor, data_vencimento FROM pdd_perdas WHERE codigo_contrato LIKE '%47661%' OR codigo_contrato_norm LIKE '%47661%'");
$stmt->execute();
$pdd = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($pdd as $r) {
    echo "  ID={$r['id']}, codigo_contrato='{$r['codigo_contrato']}', norm='{$r['codigo_contrato_norm']}', venda='{$r['codigo_venda']}', cpf='{$r['cpf_cnpj']}', valor={$r['valor']}, venc={$r['data_vencimento']}\n";
}
if (empty($pdd)) echo "  NENHUM REGISTRO ENCONTRADO\n";

// 4. Check pdd_pagos
echo "\n--- PDD PAGOS ---\n";
$stmt = $db->prepare("SELECT id, codigo_norm, titulo_norm FROM pdd_pagos WHERE codigo_norm LIKE '%47661%' OR titulo_norm LIKE '%47661%'");
$stmt->execute();
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($pagos as $r) {
    echo "  ID={$r['id']}, codigo_norm='{$r['codigo_norm']}', titulo_norm='{$r['titulo_norm']}'\n";
}
if (empty($pagos)) echo "  NENHUM REGISTRO ENCONTRADO\n";

// 5. Check spc_historico_removidos
echo "\n--- SPC HISTORICO REMOVIDOS ---\n";
$stmt = $db->prepare("SELECT id, original_id, contrato, tp_contrato, contratante, cpf_cnpj, vencimento, data_remocao, motivo_remocao FROM spc_historico_removidos WHERE contrato LIKE '%47661%'");
$stmt->execute();
$removidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($removidos as $r) {
    $vencIsNull = is_null($r['vencimento']) ? 'SIM' : 'NAO';
    echo "  ID={$r['id']}, original_id={$r['original_id']}, contrato='{$r['contrato']}', tp_contrato='{$r['tp_contrato']}', cpf='{$r['cpf_cnpj']}', vencimento='{$r['vencimento']}' (NULL? $vencIsNull), data_remocao='{$r['data_remocao']}', motivo='{$r['motivo_remocao']}'\n";
}
if (empty($removidos)) echo "  NENHUM REGISTRO ENCONTRADO\n";

// 6. Check spc_ignorados  
echo "\n--- SPC IGNORADOS ---\n";
$stmt = $db->prepare("SELECT * FROM spc_ignorados WHERE contrato_norm LIKE '%47661%'");
$stmt->execute();
$ignorados = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($ignorados as $r) {
    echo "  ";
    print_r($r);
}
if (empty($ignorados)) echo "  NENHUM REGISTRO ENCONTRADO\n";

// 7. Simulate the JOIN
echo "\n--- SIMULACAO DO JOIN DE EXCLUSAO ---\n";
if (!empty($inclusos)) {
    foreach ($inclusos as $s) {
        $spcVencimento = $s['vencimento'];
        $spcContrato = $s['contrato'];
        $spcContratoNorm = $s['contrato_norm'];
        $spcCpfNorm = $s['cpf_cnpj_norm'];
        $spcVenda = $s['venda'];
        
        echo "Registro SPC Inclusos ID={$s['id']}: contrato='{$spcContrato}', contrato_norm='{$spcContratoNorm}', cpf_norm='{$spcCpfNorm}', vencimento='{$spcVencimento}', venda='{$spcVenda}'\n\n";
        
        // Check parcelas match
        echo "  CHECK 1 - parcelas_em_aberto (cpf_norm + contrato_norm):\n";
        $stmt = $db->prepare("SELECT id FROM parcelas_em_aberto WHERE cpf_cnpj_norm = ? AND contrato_norm = ?");
        $stmt->execute([$spcCpfNorm, $spcContratoNorm]);
        $match = $stmt->fetch();
        echo "    Match: " . ($match ? "SIM (p.id IS NOT NULL -> nao entra na exclusao)" : "NAO (p.id IS NULL -> candidato a exclusao)") . "\n";
        
        // Check parcelas by contrato_norm only (for 'CPF Divergente' motivo)
        echo "  CHECK 1b - parcelas_em_aberto (apenas contrato_norm):\n";
        $stmt = $db->prepare("SELECT id, cpf_cnpj_norm FROM parcelas_em_aberto WHERE contrato_norm = ?");
        $stmt->execute([$spcContratoNorm]);
        $matchContrato = $stmt->fetch();
        echo "    Match: " . ($matchContrato ? "SIM (cpf_norm={$matchContrato['cpf_cnpj_norm']}) -> motivo seria 'CPF Divergente'" : "NAO") . "\n";
        
        // Check pdd_perdas match
        echo "  CHECK 2 - pdd_perdas (contrato_norm):\n";
        $stmt = $db->prepare("SELECT id, codigo_venda FROM pdd_perdas WHERE codigo_contrato_norm = ?");
        $stmt->execute([$spcContratoNorm]);
        $pddMatch = $stmt->fetch();
        echo "    Match: " . ($pddMatch ? "SIM (pp.id={$pddMatch['id']}, venda='{$pddMatch['codigo_venda']}')" : "NAO (pp.id IS NULL)") . "\n";
        
        // Check pdd_pagos match
        if ($pddMatch) {
            echo "  CHECK 2b - pdd_pagos (via pdd_perdas):\n";
            $stmt = $db->prepare("SELECT id, titulo_norm, codigo_norm FROM pdd_pagos WHERE (titulo_norm = ? AND ? != '') OR (codigo_norm = ? AND ? != '')");
            $stmt->execute([$pddMatch['codigo_venda'], $pddMatch['codigo_venda'], $spcContratoNorm, $spcContratoNorm]);
            $pgMatch = $stmt->fetch();
            echo "    Match: " . ($pgMatch ? "SIM (pg_check.id={$pgMatch['id']})" : "NAO") . "\n";
        }
        
        // Check direct pdd_pagos match
        echo "  CHECK 2c - pdd_pagos DIRETO (contrato_norm ou venda):\n";
        $stmt = $db->prepare("SELECT id, titulo_norm, codigo_norm FROM pdd_pagos WHERE (codigo_norm = ? AND ? != '') OR (titulo_norm = ? AND ? != '') OR (titulo_norm = ? AND ? != '')");
        $stmt->execute([$spcContratoNorm, $spcContratoNorm, $spcContratoNorm, $spcContratoNorm, $spcVenda, $spcVenda]);
        $pgDirect = $stmt->fetch();
        echo "    Match: " . ($pgDirect ? "SIM -> ENTRARIA na UNION 2 (PDD PAGO)" : "NAO") . "\n";
        
        // Check historico match
        echo "  CHECK 3 - spc_historico_removidos (contrato + vencimento):\n";
        $stmt = $db->prepare("SELECT id, contrato, vencimento, motivo_remocao FROM spc_historico_removidos WHERE contrato = ? AND (vencimento IS NULL OR vencimento = ?)");
        $stmt->execute([$spcContrato, $spcVencimento]);
        $histMatch = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($histMatch)) {
            echo "    >>> MATCH ENCONTRADO! Este e o motivo de NAO aparecer na listagem de exclusao:\n";
            foreach ($histMatch as $h) {
                echo "      ID={$h['id']}, contrato='{$h['contrato']}', vencimento='{$h['vencimento']}', motivo='{$h['motivo_remocao']}'\n";
            }
        } else {
            echo "    SEM MATCH - historico NAO deveria bloquear\n";
        }

        // Final verdict
        echo "\n  === VEREDICTO ===\n";
        $inParcelas = (bool)$match;
        $inHistorico = !empty($histMatch);
        $inPddPerdas = (bool)$pddMatch;
        
        if ($inParcelas) {
            echo "  -> Nao aparece na exclusao porque EXISTE em parcelas_em_aberto com mesmo cpf+contrato\n";
        } elseif ($inHistorico) {
            echo "  -> Nao aparece na exclusao porque ja foi REMOVIDO anteriormente (spc_historico_removidos com match de contrato+vencimento)\n";
            echo "  -> SOLUCAO: Remover o registro do historico que esta bloqueando, ou excluir manualmente\n";
        } elseif ($inPddPerdas && !isset($pgMatch)) {
            echo "  -> Nao aparece na exclusao porque esta em pdd_perdas (NAO pago)\n";
        } else {
            echo "  -> DEVERIA aparecer na exclusao. Se nao aparece, pode ser um bug.\n";
        }
        echo "\n";
    }
} else {
    echo "  Nenhum registro em spc_inclusos para simular.\n";
}

echo "\n=== FIM DEBUG ===\n";
