<?php
/**
 * Debug script para verificar por que endereço não está atualizando
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== DEBUG: Address Update Issue ===\n\n";

// 1. Verificar estrutura da tabela spc_inclusos
echo "1. Estrutura da tabela spc_inclusos:\n";
$stmt = $db->query("DESCRIBE spc_inclusos");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$addressFields = ['rua', 'numero', 'bairro', 'cep', 'cidade', 'estado'];
echo "  Campos de endereço:\n";
foreach ($columns as $col) {
    if (in_array($col['Field'], $addressFields)) {
        echo "    ✓ {$col['Field']} ({$col['Type']})\n";
    }
}

// 2. Verificar registros SEM endereço
echo "\n2. Registros SEM endereço completo:\n";
$query = "SELECT COUNT(*) as total,
          SUM(CASE WHEN rua IS NULL OR rua = '' THEN 1 ELSE 0 END) as sem_rua,
          SUM(CASE WHEN numero IS NULL OR numero = '' THEN 1 ELSE 0 END) as sem_numero,
          SUM(CASE WHEN bairro IS NULL OR bairro = '' THEN 1 ELSE 0 END) as sem_bairro,
          SUM(CASE WHEN cidade IS NULL OR cidade = '' THEN 1 ELSE 0 END) as sem_cidade,
          SUM(CASE WHEN estado IS NULL OR estado = '' THEN 1 ELSE 0 END) as sem_estado
          FROM spc_inclusos";

$stmt = $db->query($query);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "  Total de registros: {$stats['total']}\n";
echo "  Sem Rua: {$stats['sem_rua']}\n";
echo "  Sem Numero: {$stats['sem_numero']}\n";
echo "  Sem Bairro: {$stats['sem_bairro']}\n";
echo "  Sem Cidade: {$stats['sem_cidade']}\n";
echo "  Sem Estado: {$stats['sem_estado']}\n";

// 3. Mostrar alguns exemplos
echo "\n3. Exemplos de registros (primeiros 5):\n";
$query = "SELECT contrato, contrato_norm, contratante, rua, numero, bairro, cidade, estado, cep 
          FROM spc_inclusos 
          LIMIT 5";

$stmt = $db->query($query);
$examples = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($examples as $i => $ex) {
    echo "\n  Registro " . ($i + 1) . ":\n";
    echo "    Contrato: {$ex['contrato']} (norm: {$ex['contrato_norm']})\n";
    echo "    Contratante: {$ex['contratante']}\n";
    echo "    Rua: " . ($ex['rua'] ?: 'NULL') . "\n";
    echo "    Número: " . ($ex['numero'] ?: 'NULL') . "\n";
    echo "    Bairro: " . ($ex['bairro'] ?: 'NULL') . "\n";
    echo "    Cidade: " . ($ex['cidade'] ?: 'NULL') . "\n";
    echo "    Estado: " . ($ex['estado'] ?: 'NULL') . "\n";
    echo "    CEP: " . ($ex['cep'] ?: 'NULL') . "\n";
}

// 4. Verificar últimas importações enrichment
echo "\n4. Últimas importações de tipo 'enrichment':\n";
$query = "SELECT id, filename, imported_at 
          FROM import_batches 
          WHERE type = 'enrichment' 
          ORDER BY id DESC 
          LIMIT 5";

$stmt = $db->query($query);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($batches) > 0) {
    foreach ($batches as $batch) {
        echo "  - Batch {$batch['id']}: {$batch['filename']} em {$batch['imported_at']}\n";
    }
} else {
    echo "  ⚠ Nenhuma importação de tipo 'enrichment' encontrada!\n";
}

echo "\n=== FIM DEBUG ===\n";
echo "\nPróximos passos:\n";
echo "1. Verifique a planilha de importação - ela tem as colunas corretas?\n";
echo "2. Tente importar uma planilha pequena de teste\n";
echo "3. Verifique os logs do PHP para erros\n";
