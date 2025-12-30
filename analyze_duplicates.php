<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Análise de Duplicatas - CPF 04794874308 ===\n\n";
    
    // Buscar todos os registros deste CPF
    $sql = "
        SELECT id, cpf_cnpj, cpf_cnpj_norm, contratante, contrato, contrato_norm, 
               vencimento, debito, venda, parcela
        FROM parcelas_em_aberto
        WHERE cpf_cnpj_norm LIKE '%04794874308%' OR cpf_cnpj LIKE '%04794874308%'
        ORDER BY vencimento, id
    ";
    
    $stmt = $db->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total de registros encontrados: " . count($records) . "\n\n";
    
    foreach ($records as $i => $rec) {
        echo "Registro " . ($i + 1) . ":\n";
        echo "  ID: {$rec['id']}\n";
        echo "  CPF: {$rec['cpf_cnpj']} (norm: {$rec['cpf_cnpj_norm']})\n";
        echo "  Nome: {$rec['contratante']}\n";
        echo "  Contrato: {$rec['contrato']} (norm: {$rec['contrato_norm']})\n";
        echo "  Vencimento: {$rec['vencimento']}\n";
        echo "  Valor: {$rec['debito']}\n";
        echo "  Venda: {$rec['venda']}\n";
        echo "  Parcela: {$rec['parcela']}\n";
        echo "  ---\n";
    }
    
    // Agrupar por vencimento
    echo "\n=== Agrupamento por Vencimento ===\n";
    $byDate = [];
    foreach ($records as $rec) {
        $date = $rec['vencimento'];
        if (!isset($byDate[$date])) {
            $byDate[$date] = [];
        }
        $byDate[$date][] = $rec['id'];
    }
    
    foreach ($byDate as $date => $ids) {
        echo "Vencimento $date: " . count($ids) . " registros (IDs: " . implode(', ', $ids) . ")\n";
    }
    
    // Verificar se há duplicatas exatas (mesmo vencimento)
    echo "\n=== Duplicatas Exatas (mesmo vencimento) ===\n";
    $sql = "
        SELECT vencimento, debito, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM parcelas_em_aberto
        WHERE cpf_cnpj_norm LIKE '%04794874308%'
        AND contrato_norm = '61'
        GROUP BY vencimento, debito
        HAVING count > 1
    ";
    
    $stmt = $db->query($sql);
    $dups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dups)) {
        echo "Nenhuma duplicata exata encontrada.\n";
    } else {
        foreach ($dups as $dup) {
            echo "Vencimento {$dup['vencimento']}, Valor {$dup['debito']}: {$dup['count']} registros (IDs: {$dup['ids']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
