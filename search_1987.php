<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== Procurando variações do contrato 1987 ===\n\n";

$patterns = ['1987', '1987P1', '1987 P1', '%1987%'];

foreach ($patterns as $pattern) {
    echo "Buscando por: $pattern\n";
    
    $stmt = $db->prepare("SELECT codigo_contrato, codigo_contrato_norm FROM pdd_perdas WHERE codigo_contrato LIKE ? OR codigo_contrato_norm LIKE ? LIMIT 1");
    $stmt->execute([$pattern, $pattern]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "  ✓ Encontrado: {$result['codigo_contrato']} (norm: {$result['codigo_contrato_norm']})\n";
    } else {
        echo "  ✗ Não encontrado\n";
    }
    echo "\n";
}
