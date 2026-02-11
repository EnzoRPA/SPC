<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== ANALISE COMPARATIVA: pdd_perdas vs spc_inclusos ===\n\n";
    
    // Total em cada tabela
    $stmt = $db->query("SELECT COUNT(DISTINCT codigo_contrato_norm) as total FROM pdd_perdas");
    $pddTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(DISTINCT contrato_norm) as total FROM spc_inclusos");
    $spcTotal = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Total de contratos únicos:\n";
    echo "  pdd_perdas: $pddTotal\n";
    echo "  spc_inclusos: $spcTotal\n\n";
    
    // Contratos que existem em pdd mas não em spc
    $stmt = $db->query("
        SELECT COUNT(DISTINCT p.codigo_contrato_norm) as missing
        FROM pdd_perdas p
        LEFT JOIN spc_inclusos s ON p.codigo_contrato_norm = s.contrato_norm
        WHERE s.contrato_norm IS NULL
    ");
    $missing = $stmt->fetch(PDO::FETCH_ASSOC)['missing'];
    
    echo "Contratos que existem em pdd_perdas mas NÃO em spc_inclusos: $missing\n\n";
    
    if ($missing > 0) {
        echo "Exemplos de contratos faltantes:\n";
        $stmt = $db->query("
            SELECT DISTINCT p.codigo_contrato_norm
            FROM pdd_perdas p
            LEFT JOIN spc_inclusos s ON p.codigo_contrato_norm = s.contrato_norm
            WHERE s.contrato_norm IS NULL
            LIMIT 10
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - " . $row['codigo_contrato_norm'] . "\n";
        }
    }
    
    echo "\n=== CONCLUSÃO ===\n";
    echo "É normal que alguns contratos existam apenas em uma tabela?\n";
    echo "Ou todos os contratos de pdd_perdas deveriam estar também em spc_inclusos?\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
