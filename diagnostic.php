<?php
echo "=== DIAGNÓSTICO: Por que CPF não atualiza? ===\n\n";

// Possíveis causas
$causes = [
    '1' => [
        'title' => 'Coluna CPF não foi detectada',
        'details' => 'O nome da coluna "CPF_CNPJ" ou "CPF_CNPJ_PL_CONTRATANTE" não foi reconhecido',
        'solution' => 'Verificar logs do PHP para ver quais colunas foram detectadas'
    ],
    '2' => [
        'title' => 'Contrato não existe no banco',
        'details' => 'O contrato 1987 precisa existir ANTES de poder atualizar',
        'solution' => 'Verificamos que existe, então NÃO é este o problema'
    ],
    '3' => [
        'title' => 'Normalização não dá match',
        'details' => 'contrato_norm na planilha ≠ contrato_norm no banco',
        'solution' => 'Testar normalização do contrato'
    ],
    '4' => [
        'title' => 'CPF está vazio na planilha',
        'details' => 'O valor da célula do CPF pode estar vazio ou NULL',
        'solution' => 'Verificar se a planilha realmente tem CPF preenchido'
    ],
    '5' => [
        'title' => 'UPDATE não está sendo executado',
        'details' => 'O código pode não estar ch egando na parte do UPDATE',
        'solution' => 'Verificar logs para ver quantos registros foram atualizados'
    ],
];

echo "Possíveis causas:\n\n";
foreach ($causes as $num => $cause) {
    echo "$num. {$cause['title']}\n";
    echo "   Problema: {$cause['details']}\n";
    echo "   Solução: {$cause['solution']}\n\n";
}

echo "\n=== AÇÕES RECOMENDADAS ===\n\n";
echo "1. Compartilhe o arquivo 'teste1.xlsx' para eu analisar\n";
echo "   OU\n";
echo "2. Verifique os logs do PHP:\n";
echo "   - Procure por 'DataEnrichmentImporter - Columns detected:'\n";
echo "   - Veja se CPF aparece como 'Col X' ou 'NOT FOUND'\n\n";
echo "3. Execute este comando para ver os logs recentes:\n";

$logFile = ini_get('error_log');
if ($logFile && $logFile !== 'syslog') {
    echo "   Get-Content '$logFile' -Tail 100 | Select-String 'DataEnrichmentImporter'\n\n";
} else {
    echo "   Verifique o log do seu servidor web (Apache/PHP)\n\n";
}

echo "4. Tente criar uma planilha SIMPLES com apenas:\n";
echo "   CONTRATO | NOME | CPF\n";
echo "   1987     | Teste | 12345678900\n\n";
echo "   E importe novamente\n\n";
