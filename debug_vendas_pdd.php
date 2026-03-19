<?php
require_once __DIR__ . '/config/db.php';
$db = new Database();
$pdo = $db->getConnection();

$contratos = ['1504','30341','52437','52436','52310'];
$in = implode(',', array_map(fn($c) => "'$c'", $contratos));

$result = [];

$result['pdd_perdas'] = $pdo->query("
    SELECT id, codigo_venda, codigo_contrato, codigo_contrato_norm, data_vencimento, nome, cpf_cnpj, valor
    FROM pdd_perdas
    WHERE codigo_contrato_norm IN ($in)
    ORDER BY codigo_contrato_norm
")->fetchAll(PDO::FETCH_ASSOC);

$result['spc_inclusos'] = $pdo->query("
    SELECT id, contrato, contrato_norm, venda, cpf_cnpj, vencimento, tp_contrato
    FROM spc_inclusos
    WHERE contrato_norm IN ($in)
    ORDER BY contrato_norm
")->fetchAll(PDO::FETCH_ASSOC);

$result['pdd_pagos_check'] = $pdo->query("
    SELECT pg.id, pg.titulo_norm, pg.codigo_norm
    FROM pdd_pagos pg
    WHERE pg.titulo_norm IN ('5230354','5236052','5245613','5245612','5251225')
       OR pg.codigo_norm IN ($in)
")->fetchAll(PDO::FETCH_ASSOC);

$result['parcelas_em_aberto'] = $pdo->query("
    SELECT id, contrato, contrato_norm, venda FROM parcelas_em_aberto
    WHERE contrato_norm IN ($in)
")->fetchAll(PDO::FETCH_ASSOC);

file_put_contents(__DIR__ . '/debug_vendas_result.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "OK: debug_vendas_result.json\n";
