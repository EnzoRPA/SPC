<?php
// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Script para rodar limpeza manual de duplicatas
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Comparator.php';

use App\Comparator;

// Output HTML para visualização no browser
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Limpeza de Duplicatas</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Limpeza Manual de Duplicatas</h1>
    <pre><?php
    
try {
    echo "Conectando ao banco de dados...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Conectado com sucesso!\n\n";
    
    echo "=== Executando Limpeza Manual de Duplicatas ===\n\n";
    
    $comparator = new Comparator($db);
    
    echo "Iniciando limpeza...\n";
    $removed = $comparator->limparVendasDuplicadas();
    
    echo "\n=== Resultado ===\n";
    echo "Total de registros removidos: $removed\n";
    
    if ($removed > 0) {
        echo "\n<span class='success'>✓ Limpeza concluída com sucesso!</span>\n";
        echo "Verifique o arquivo debug_cleanup_log.txt para mais detalhes.\n";
    } else {
        echo "\n<span class='success'>✓ Nenhuma duplicata encontrada.</span>\n";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erro ao executar limpeza:</span>\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}

?></pre>
    <br>
    <a href='public/index.php?page=report'>← Voltar para Relatório</a>
</body>
</html>
