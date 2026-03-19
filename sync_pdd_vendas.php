<?php
/**
 * Sincroniza pdd_perdas: para cada registro em spc_inclusos cuja VENDA não existe em pdd_perdas
 * mas cujo CONTRATO existe em pdd_perdas, insere uma cópia com o código de venda correto.
 * Isso evita que PDDs válidos apareçam na lista de exclusão.
 */
require_once __DIR__ . '/config/db.php';
$db = new Database();
$pdo = $db->getConnection();

// Busca registros do SPC cujo contrato está em pdd_perdas mas a venda não está
$stmt = $pdo->query("
    SELECT DISTINCT s.contrato_norm, s.venda, s.vencimento
    FROM spc_inclusos s
    WHERE s.venda IS NOT NULL AND s.venda != ''
      AND NOT EXISTS (
          SELECT 1 FROM pdd_perdas pp WHERE pp.codigo_venda = s.venda
      )
      AND EXISTS (
          SELECT 1 FROM pdd_perdas pp WHERE pp.codigo_contrato_norm = s.contrato_norm
      )
      AND NOT EXISTS (
          SELECT 1 FROM parcelas_em_aberto p WHERE p.contrato_norm = s.contrato_norm
      )
");
$toSync = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($toSync)) {
    echo "Nenhum registro para sincronizar.\n";
    exit;
}

echo count($toSync) . " registro(s) encontrado(s) para sincronizar:\n\n";

$inserted = 0;
foreach ($toSync as $row) {
    // Busca o pdd_perdas mais recente para esse contrato como template
    $tmpl = $pdo->prepare("
        SELECT * FROM pdd_perdas WHERE codigo_contrato_norm = ? ORDER BY id DESC LIMIT 1
    ");
    $tmpl->execute([$row['contrato_norm']]);
    $source = $tmpl->fetch(PDO::FETCH_ASSOC);

    if (!$source) continue;

    echo "Contrato {$row['contrato_norm']}: copiando venda {$source['codigo_venda']} → {$row['venda']}\n";

    // Verifica se já existe (proteção dupla)
    $check = $pdo->prepare("SELECT id FROM pdd_perdas WHERE codigo_venda = ? LIMIT 1");
    $check->execute([$row['venda']]);
    if ($check->fetch()) {
        echo "  → Venda {$row['venda']} já existe. Pulando.\n";
        continue;
    }

    // Insere cópia com a nova venda
    $ins = $pdo->prepare("
        INSERT INTO pdd_perdas
            (batch_id, codigo_venda, codigo_contrato, data_vencimento, codigo_contrato_norm, nome, valor, cpf_cnpj, endereco)
        VALUES
            (:batch_id, :codigo_venda, :codigo_contrato, :data_vencimento, :codigo_contrato_norm, :nome, :valor, :cpf_cnpj, :endereco)
    ");
    $ins->execute([
        ':batch_id'              => $source['batch_id'],
        ':codigo_venda'          => $row['venda'],
        ':codigo_contrato'       => $source['codigo_contrato'],
        ':data_vencimento'       => $source['data_vencimento'],
        ':codigo_contrato_norm'  => $source['codigo_contrato_norm'],
        ':nome'                  => $source['nome'],
        ':valor'                 => $source['valor'],
        ':cpf_cnpj'              => $source['cpf_cnpj'],
        ':endereco'              => $source['endereco'] ?? null,
    ]);

    echo "  → Inserido com sucesso.\n";
    $inserted++;
}

echo "\nTotal inserido: $inserted\n";
