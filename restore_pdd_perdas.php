<?php
require_once __DIR__ . '/config/db.php';
$db = new Database();
$pdo = $db->getConnection();

// Busca o batch_id mais recente de pdd_perdas para usar como referência
$lastBatch = $pdo->query("SELECT MAX(batch_id) FROM pdd_perdas")->fetchColumn();

// Encontra registros em spc_inclusos cuja VENDA não existe em pdd_perdas
// mas cujo CONTRATO existe (foram deletados indevidamente pelo rollback)
$stmt = $pdo->query("
    SELECT DISTINCT s.contrato_norm, s.venda
    FROM spc_inclusos s
    WHERE s.venda IS NOT NULL AND s.venda != ''
      AND NOT EXISTS (
          SELECT 1 FROM pdd_perdas pp WHERE pp.codigo_venda = s.venda
      )
      AND EXISTS (
          SELECT 1 FROM pdd_perdas pp WHERE pp.codigo_contrato_norm = s.contrato_norm
      )
");
$toRestore = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo count($toRestore) . " entradas para restaurar\n";

$stmtIns = $pdo->prepare("
    INSERT INTO pdd_perdas
        (batch_id, codigo_venda, codigo_contrato, data_vencimento, codigo_contrato_norm, nome, valor, cpf_cnpj, endereco)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$restored = 0;
foreach ($toRestore as $row) {
    $tmpl = $pdo->prepare("SELECT * FROM pdd_perdas WHERE codigo_contrato_norm = ? ORDER BY id ASC LIMIT 1");
    $tmpl->execute([$row['contrato_norm']]);
    $source = $tmpl->fetch(PDO::FETCH_ASSOC);
    if (!$source) continue;

    $chk = $pdo->prepare("SELECT id FROM pdd_perdas WHERE codigo_venda = ? LIMIT 1");
    $chk->execute([$row['venda']]);
    if ($chk->fetch()) continue;

    $stmtIns->execute([
        $lastBatch,
        $row['venda'],
        $source['codigo_contrato'],
        $source['data_vencimento'],
        $source['codigo_contrato_norm'],
        $source['nome'],
        $source['valor'],
        $source['cpf_cnpj'],
        $source['endereco'] ?? null,
    ]);
    $restored++;
}

echo "Restauradas: $restored entradas\n";
echo "Pronto!\n";
