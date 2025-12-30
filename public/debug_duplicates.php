<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';

$database = new Database();
$db = $database->getConnection();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico de Duplicatas</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        pre { background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        table { border-collapse: collapse; width: 100%; background: white; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #f0f0f0; }
        .dup { background: #ffe0e0; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Duplicatas</h1>
    <pre><?php

echo "Buscando registros do CPF 04794874308...\n\n";

$sql = "
    SELECT id, cpf_cnpj, cpf_cnpj_norm, contratante, contrato, contrato_norm, 
           vencimento, debito, venda, parcela
    FROM parcelas_em_aberto
    WHERE cpf_cnpj LIKE '%04794874308%' OR cpf_cnpj_norm LIKE '%04794874308%'
    ORDER BY vencimento, id
";

$stmt = $db->query($sql);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total encontrado: " . count($records) . " registros\n\n";

?></pre>

<?php if (!empty($records)): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>CPF</th>
            <th>CPF Norm</th>
            <th>Nome</th>
            <th>Contrato</th>
            <th>Vencimento</th>
            <th>Valor</th>
            <th>Venda</th>
        </tr>
        <?php 
        $prev = null;
        foreach ($records as $rec): 
            $isDup = false;
            if ($prev) {
                $isDup = (
                    $rec['cpf_cnpj_norm'] == $prev['cpf_cnpj_norm'] &&
                    $rec['contratante'] == $prev['contratante'] &&
                    $rec['contrato_norm'] == $prev['contrato_norm'] &&
                    $rec['vencimento'] == $prev['vencimento'] &&
                    $rec['debito'] == $prev['debito']
                );
            }
            $class = $isDup ? 'dup' : '';
        ?>
        <tr class="<?= $class ?>">
            <td><?= $rec['id'] ?></td>
            <td><?= htmlspecialchars($rec['cpf_cnpj']) ?></td>
            <td><?= htmlspecialchars($rec['cpf_cnpj_norm']) ?></td>
            <td><?= htmlspecialchars($rec['contratante']) ?></td>
            <td><?= htmlspecialchars($rec['contrato']) ?></td>
            <td><?= $rec['vencimento'] ?></td>
            <td><?= $rec['debito'] ?></td>
            <td><?= htmlspecialchars($rec['venda']) ?></td>
        </tr>
        <?php 
            $prev = $rec;
        endforeach; 
        ?>
    </table>
    
    <pre><?php
    echo "\n=== Análise de Duplicatas ===\n";
    echo "Linhas em vermelho = possíveis duplicatas\n\n";
    
    // Verificar duplicatas exatas
    $sql = "
        SELECT cpf_cnpj_norm, contratante, contrato_norm, vencimento, debito,
               COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM parcelas_em_aberto
        WHERE cpf_cnpj LIKE '%04794874308%'
        GROUP BY cpf_cnpj_norm, contratante, contrato_norm, vencimento, debito
        HAVING count > 1
    ";
    
    $stmt = $db->query($sql);
    $dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dups)) {
        echo "✓ Nenhuma duplicata exata encontrada para este CPF\n";
    } else {
        echo "✗ Duplicatas encontradas:\n";
        foreach ($dups as $dup) {
            echo "  Vencimento: {$dup['vencimento']}, Valor: {$dup['debito']}\n";
            echo "  Count: {$dup['count']}, IDs: {$dup['ids']}\n\n";
        }
    }
    
    ?></pre>
<?php else: ?>
    <p>Nenhum registro encontrado para este CPF.</p>
<?php endif; ?>

    <br>
    <a href='index.php?page=report'>← Voltar para Relatório</a>
</body>
</html>
