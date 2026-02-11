<?php
require_once __DIR__ . '/config/db.php';

$db = (new Database())->getConnection();

echo "=== INVESTIGAÇÃO DETALHADA: Contrato 1987 ===\n\n";

// 1. Mostrar TODOS os campos do contrato 1987
echo "1. Dados completos em pdd_perdas:\n";
$stmt = $db->query("SELECT * FROM pdd_perdas WHERE codigo_contrato = '1987' LIMIT 1");
$pdd = $stmt->fetch(PDO::FETCH_ASSOC);

if ($pdd) {
    foreach ($pdd as $col => $val) {
        echo "  $col: " . ($val ?: 'NULL') . "\n";
    }
} else {
    echo "  ✗ Contrato 1987 não encontrado!\n";
}

echo "\n2. Dados completos em spc_inclusos:\n";
$stmt = $db->query("SELECT * FROM spc_inclusos WHERE contrato = '1987' LIMIT 1");
$spc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($spc) {
    foreach ($spc as $col => $val) {
        echo "  $col: " . ($val ?: 'NULL') . "\n";
    }
} else {
    echo "  ✗ Contrato 1987 não encontrado!\n";
}

echo "\n3. Últimas 3 importações 'enrichment':\n";
$stmt = $db->query("SELECT id, filename, imported_at FROM import_batches WHERE type = 'enrichment' ORDER BY id DESC LIMIT 3");
while ($batch = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Batch #{$batch['id']}: {$batch['filename']} em {$batch['imported_at']}\n";
}
