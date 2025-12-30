<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Diagnóstico de Duplicatas ===\n\n";
    
    // Verificar duplicatas na tabela parcelas_em_aberto
    echo "1. Verificando duplicatas por VENDA em parcelas_em_aberto:\n";
    $sql = "
        SELECT venda, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM parcelas_em_aberto
        WHERE venda IS NOT NULL AND venda != ''
        GROUP BY venda
        HAVING count > 1
        LIMIT 10
    ";
    $stmt = $db->query($sql);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "  ✓ Nenhuma duplicata por venda encontrada\n\n";
    } else {
        echo "  ✗ Encontradas " . count($duplicates) . " vendas duplicadas:\n";
        foreach ($duplicates as $dup) {
            echo "    - Venda: {$dup['venda']}, Count: {$dup['count']}, IDs: {$dup['ids']}\n";
        }
        echo "\n";
    }
    
    // Verificar duplicatas por combinação de campos
    echo "2. Verificando duplicatas por CAMPOS em parcelas_em_aberto:\n";
    $sql = "
        SELECT 
            cpf_cnpj_norm, 
            contratante, 
            contrato_norm, 
            vencimento, 
            debito,
            COUNT(*) as count,
            GROUP_CONCAT(id ORDER BY id) as ids
        FROM parcelas_em_aberto
        GROUP BY cpf_cnpj_norm, contratante, contrato_norm, vencimento, debito
        HAVING count > 1
        LIMIT 10
    ";
    $stmt = $db->query($sql);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "  ✓ Nenhuma duplicata por campos encontrada\n\n";
    } else {
        echo "  ✗ Encontradas " . count($duplicates) . " duplicatas por campos:\n";
        foreach ($duplicates as $dup) {
            echo "    - CPF: {$dup['cpf_cnpj_norm']}, Nome: {$dup['contratante']}, Contrato: {$dup['contrato_norm']}\n";
            echo "      Vencimento: {$dup['vencimento']}, Valor: {$dup['debito']}\n";
            echo "      Count: {$dup['count']}, IDs: {$dup['ids']}\n\n";
        }
    }
    
    // Verificar o caso específico da imagem
    echo "3. Verificando caso específico (CPF 04794874308):\n";
    $sql = "
        SELECT id, cpf_cnpj, cpf_cnpj_norm, contratante, contrato, contrato_norm, vencimento, debito, venda
        FROM parcelas_em_aberto
        WHERE cpf_cnpj_norm LIKE '%04794874308%' OR cpf_cnpj LIKE '%04794874308%'
        ORDER BY id
    ";
    $stmt = $db->query($sql);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "  ✓ Nenhum registro encontrado para CPF 04794874308\n\n";
    } else {
        echo "  Encontrados " . count($records) . " registros:\n";
        foreach ($records as $rec) {
            echo "    ID: {$rec['id']}\n";
            echo "    CPF: {$rec['cpf_cnpj']} (norm: {$rec['cpf_cnpj_norm']})\n";
            echo "    Nome: {$rec['contratante']}\n";
            echo "    Contrato: {$rec['contrato']} (norm: {$rec['contrato_norm']})\n";
            echo "    Vencimento: {$rec['vencimento']}\n";
            echo "    Valor: {$rec['debito']}\n";
            echo "    Venda: {$rec['venda']}\n";
            echo "    ---\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
