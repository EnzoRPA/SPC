<?php
/**
 * Desfaz o sync agressivo: remove as entradas de pdd_perdas que foram inseridas 
 * para contratos como 1504, 30341, etc. (que ainda devem aparecer na lista de exclusão).
 * 
 * Mantém as entradas para 4833 e 4399 que estão corretamente protegidas.
 * 
 * Critério de remoção: entradas onde o contrato TEM outras entradas MAIS ANTIGAS
 * em pdd_perdas (ou seja, a venda inserida é "nova" e não estava no arquivo original).
 * Identificamos as entradas do sync porque elas têm codigo_venda diferente de todas
 * as entradas originais do contrato, e a venda existe em spc_inclusos.
 * 
 * Abordagem: deletar entradas onde:
 * - A venda (codigo_venda) existe em spc_inclusos
 * - O contrato tem outra entrada em pdd_perdas com id menor (era preexistente)
 * - A venda NÃO é de 4833 e 4399 (contratos que queremos proteger)
 * - O contrato NÃO está em parcelas_em_aberto
 */
require_once __DIR__ . '/config/db.php';
$db = new Database();
$pdo = $db->getConnection();

// Visualizar o que será deletado antes de deletar
$preview = $pdo->query("
    SELECT pp.id, pp.codigo_venda, pp.codigo_contrato, pp.codigo_contrato_norm
    FROM pdd_perdas pp
    WHERE EXISTS (
        SELECT 1 FROM spc_inclusos s WHERE s.venda = pp.codigo_venda
    )
    AND EXISTS (
        SELECT 1 FROM pdd_perdas pp2
        WHERE pp2.codigo_contrato_norm = pp.codigo_contrato_norm
        AND pp2.id < pp.id
    )
    AND NOT EXISTS (
        SELECT 1 FROM parcelas_em_aberto p WHERE p.contrato_norm = pp.codigo_contrato_norm
    )
    -- Mantém as protegidas (4833 e 4399 que o usuário confirmou como PDDs válidos)
    AND pp.codigo_contrato_norm NOT IN ('4833', '4399')
    ORDER BY pp.codigo_contrato_norm
")->fetchAll(PDO::FETCH_ASSOC);

echo count($preview) . " entradas para remover:\n";
foreach ($preview as $r) {
    echo "  id={$r['id']} | venda={$r['codigo_venda']} | contrato={$r['codigo_contrato']} ({$r['codigo_contrato_norm']})\n";
}

if (empty($preview)) {
    echo "Nada para remover.\n";
    exit;
}

echo "\nDeletando...\n";
// MySQL não permite subquery na mesma tabela em DELETE direto → usa IN com derived table
$ids = $pdo->query("
    SELECT pp.id FROM pdd_perdas pp
    WHERE EXISTS (
        SELECT 1 FROM spc_inclusos s WHERE s.venda = pp.codigo_venda
    )
    AND pp.id > (
        SELECT MIN(pp2.id) FROM (SELECT id, codigo_contrato_norm FROM pdd_perdas) pp2
        WHERE pp2.codigo_contrato_norm = pp.codigo_contrato_norm
    )
    AND NOT EXISTS (
        SELECT 1 FROM parcelas_em_aberto p WHERE p.contrato_norm = pp.codigo_contrato_norm
    )
    AND pp.codigo_contrato_norm NOT IN ('4833', '4399')
")->fetchAll(PDO::FETCH_COLUMN);

if (empty($ids)) {
    echo "Nada para remover.\n";
    exit;
}

$inList = implode(',', array_map('intval', $ids));
$del = $pdo->exec("DELETE FROM pdd_perdas WHERE id IN ($inList)");
echo "Removidas: $del entradas.\n";
echo "Pronto!\n";
