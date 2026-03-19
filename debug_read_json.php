<?php
$d = json_decode(file_get_contents(__DIR__ . '/debug_vendas_result.json'), true);

echo "=== PDD_PERDAS ===\n";
foreach ($d['pdd_perdas'] as $r) {
    echo "id={$r['id']} | venda={$r['codigo_venda']} | contrato={$r['codigo_contrato']} | norm={$r['codigo_contrato_norm']} | venc={$r['data_vencimento']} | cpf={$r['cpf_cnpj']}\n";
}

echo "\n=== SPC_INCLUSOS ===\n";
foreach ($d['spc_inclusos'] as $r) {
    echo "id={$r['id']} | contrato={$r['contrato']} | norm={$r['contrato_norm']} | venda={$r['venda']} | cpf={$r['cpf_cnpj']} | tp={$r['tp_contrato']}\n";
}

echo "\n=== PDD_PAGOS (check) ===\n";
if (empty($d['pdd_pagos_check'])) {
    echo "(nenhum)\n";
} else {
    foreach ($d['pdd_pagos_check'] as $r) {
        echo "id={$r['id']} | titulo_norm={$r['titulo_norm']} | codigo_norm={$r['codigo_norm']}\n";
    }
}

echo "\n=== PARCELAS_EM_ABERTO ===\n";
if (empty($d['parcelas_em_aberto'])) {
    echo "(nenhum)\n";
} else {
    foreach ($d['parcelas_em_aberto'] as $r) {
        echo "id={$r['id']} | contrato={$r['contrato']} | norm={$r['contrato_norm']} | venda={$r['venda']}\n";
    }
}
