<?php
/**
 * Script para investigar problema de duplicados
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Investigação de Duplicados ===\n\n";

// 1. Verificar se há duplicados em spc_inclusos
echo "1. Verificando duplicados em spc_inclusos...\n";
$query = "SELECT contrato_norm, COUNT(*) as count 
          FROM spc_inclusos 
          WHERE contrato_norm IS NOT NULL AND contrato_norm != ''
          GROUP BY contrato_norm 
          HAVING count > 1 
          LIMIT 10";

$stmt = $db->query($query);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) > 0) {
    echo "  ⚠ Encontrados " . count($duplicates) . " contratos com duplicados:\n";
    foreach ($duplicates as $dup) {
        echo "    - Contrato: {$dup['contrato_norm']} - Quantidade: {$dup['count']}\n";
        
        // Mostrar detalhes dos duplicados
        $detailQuery = "SELECT id, batch_id, contrato, contratante, cpf_cnpj, vencimento 
                        FROM spc_inclusos 
                        WHERE contrato_norm = ? 
                        ORDER BY id";
        $detailStmt = $db->prepare($detailQuery);
        $detailStmt->execute([$dup['contrato_norm']]);
        $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($details as $detail) {
            echo "      ID: {$detail['id']}, Batch: {$detail['batch_id']}, Contratante: {$detail['contratante']}, CPF: {$detail['cpf_cnpj']}\n";
        }
    }
} else {
    echo "  ✓ Não foram encontrados duplicados em spc_inclusos\n";
}

echo "\n";

// 2. Verificar duplicados em pdd_perdas
echo "2. Verificando duplicados em pdd_perdas...\n";
$query = "SELECT codigo_contrato_norm, COUNT(*) as count 
          FROM pdd_perdas 
          WHERE codigo_contrato_norm IS NOT NULL AND codigo_contrato_norm != ''
          GROUP BY codigo_contrato_norm 
          HAVING count > 1 
          LIMIT 10";

$stmt = $db->query($query);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) > 0) {
    echo "  ⚠ Encontrados " . count($duplicates) . " contratos com duplicados:\n";
    foreach ($duplicates as $dup) {
        echo "    - Contrato: {$dup['codigo_contrato_norm']} - Quantidade: {$dup['count']}\n";
    }
} else {
    echo "  ✓ Não foram encontrados duplicados em pdd_perdas\n";
}

echo "\n";

// 3. Verificar batches recentes para ver se há múltiplas importações
echo "3. Verificando batches recentes...\n";
$query = "SELECT id, filename, type, imported_at 
          FROM import_batches 
          WHERE type IN ('enrichment', 'pdd_perdas', 'spc') 
          ORDER BY id DESC 
          LIMIT 10";

$stmt = $db->query($query);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "  Últimos 10 batches:\n";
foreach ($batches as $batch) {
    echo "    - ID: {$batch['id']}, Tipo: {$batch['type']}, Arquivo: {$batch['filename']}, Data: {$batch['imported_at']}\n";
}

echo "\n";

// 4. Verificar se DataEnrichmentImporter usa INSERT ou UPDATE
echo "4. Verificando código do DataEnrichmentImporter...\n";
$code = file_get_contents(__DIR__ . '/src/Importers/DataEnrichmentImporter.php');
$hasInsert = (strpos($code, 'INSERT INTO') !== false);
$hasUpdate = (strpos($code, 'UPDATE pdd_perdas') !== false || strpos($code, 'UPDATE spc_inclusos') !== false);

echo "  Usa INSERT: " . ($hasInsert ? 'SIM ⚠' : 'NÃO ✓') . "\n";
echo "  Usa UPDATE: " . ($hasUpdate ? 'SIM ✓' : 'NÃO ⚠') . "\n";

if ($hasInsert) {
    echo "  ⚠ ATENÇÃO: DataEnrichmentImporter contém  INSERT statements!\n";
    echo "            Isso pode estar causando duplicados.\n";
}

echo "\n=== FIM DA INVESTIGAÇÃO ===\n";
