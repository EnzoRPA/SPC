<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== VERIFICAÇÃO: Campos individuais em pdd_perdas ===\n\n";

$stmt = $db->prepare("
    SELECT 
        contrato,
        cpf_cnpj,
        nome,
        rua,
        numero,
        bairro,
        cep,
        cidade,
        estado,
        endereco
    FROM pdd_perdas 
    WHERE codigo_contrato = '1987'
    LIMIT 1
");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "Contrato: {$row['contrato']}\n";
    echo "CPF: " . ($row['cpf_cnpj'] ?: 'NULL') . "\n";
    echo "Nome: " . ($row['nome'] ?: 'NULL') . "\n\n";
    
    echo "CAMPOS INDIVIDUAIS:\n";
    echo "  Rua: " . ($row['rua'] ?: 'NULL') . "\n";
    echo "  Numero: " . ($row['numero'] ?: 'NULL') . "\n";
    echo "  Bairro: " . ($row['bairro'] ?: 'NULL') . "\n";
    echo "  CEP: " . ($row['cep'] ?: 'NULL') . "\n";
    echo "  Cidade: " . ($row['cidade'] ?: 'NULL') . "\n";
    echo "  Estado: " . ($row['estado'] ?: 'NULL') . "\n\n";
    
    echo "CAMPO COMPLETO:\n";
    echo "  Endereco: " . ($row['endereco'] ?: 'NULL') . "\n\n";
    
    // Verificar se os campos individuais estão preenchidos
    if ($row['rua'] && $row['numero'] && $row['bairro']) {
        echo "✅ SUCESSO! Campos individuais estão preenchidos!\n";
    } else {
        echo "❌ PROBLEMA! Campos individuais estão vazios/NULL!\n";
        echo "   Verifique se a planilha tem essas colunas.\n";
    }
} else {
    echo "❌ Contrato 1987 não encontrado!\n";
}
