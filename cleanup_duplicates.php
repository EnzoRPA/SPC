<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Limpeza de Vendas Duplicadas ===\n\n";
    
    // Tabelas que contêm o campo 'venda'
    $tables = ['spc_inclusos', 'parcelas_em_aberto'];
    
    $totalRemoved = 0;
    
    foreach ($tables as $table) {
        echo "Processando tabela: $table\n";
        
        // 1. Encontrar vendas duplicadas e manter apenas o registro com menor ID
        $sql = "
            DELETE t1 FROM $table t1
            INNER JOIN $table t2 
            WHERE t1.venda = t2.venda 
            AND t1.venda IS NOT NULL 
            AND t1.venda != ''
            AND t1.id > t2.id
        ";
        
        $removed = $db->exec($sql);
        echo "  - Removidos por venda duplicada: $removed registros\n";
        $totalRemoved += $removed;
        
        // 2. Encontrar duplicatas por combinação de campos
        // CPF/CNPJ + Nome + Contrato + Vencimento + Valor
        $nameField = ($table === 'parcelas_em_aberto') ? 'contratante' : 'contratante';
        $valueField = ($table === 'parcelas_em_aberto') ? 'debito' : 'debito';
        
        $sql = "
            DELETE t1 FROM $table t1
            INNER JOIN $table t2 
            WHERE t1.cpf_cnpj_norm = t2.cpf_cnpj_norm
            AND t1.$nameField = t2.$nameField
            AND t1.contrato_norm = t2.contrato_norm
            AND t1.vencimento = t2.vencimento
            AND t1.$valueField = t2.$valueField
            AND t1.id > t2.id
        ";
        
        $removed = $db->exec($sql);
        echo "  - Removidos por campos duplicados: $removed registros\n";
        $totalRemoved += $removed;
    }
    
    echo "\n=== Resumo ===\n";
    echo "Total de registros duplicados removidos: $totalRemoved\n";
    echo "Limpeza concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro ao limpar duplicatas: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
