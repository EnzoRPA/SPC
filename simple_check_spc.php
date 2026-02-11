<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== VERIFICACAO spc_inclusos ===\n\n";
    
    // Buscar por contrato_norm
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM spc_inclusos WHERE contrato_norm = '1987'");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total de registros com contrato_norm = '1987': " . $count['total'] . "\n\n";
    
    if ($count['total'] == 0) {
        echo "PROBLEMA: Nenhum registro encontrado!\n";
        echo "Por isso o UPDATE afeta 0 rows.\n\n";
        
        // Tentar encontrar qualquer 1987
        $stmt = $db->prepare("SELECT contrato, contrato_norm FROM spc_inclusos WHERE contrato LIKE '%1987%' LIMIT 5");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "Mas encontrei estes registros com '1987' no contrato:\n";
            foreach ($results as $row) {
                echo "  - contrato: '{$row['contrato']}', contrato_norm: '{$row['contrato_norm']}'\n";
            }
        }
    } else {
        echo "Registros encontrados! Mostrando 3 exemplos:\n";
        $stmt = $db->prepare("SELECT id, contrato, contrato_norm, cpf_cnpj FROM spc_inclusos WHERE contrato_norm = '1987' LIMIT 3");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ID: {$row['id']}, CPF: " . ($row['cpf_cnpj'] ?: 'NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
