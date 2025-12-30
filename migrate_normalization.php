<?php
// migrate_normalization.php

// require_once __DIR__ . '/public/setup_database.php'; // REMOVED to avoid path issues
require_once __DIR__ . '/src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

// Manually connect if setup_database didn't give us $db global (it usually echoes stuff)
// Let's rely on config/db.php directly to be safe and silent.
require_once __DIR__ . '/config/db.php';
$database = new Database();
$db = $database->getConnection();

echo "Starting Normalization Migration...\n";

// Tables to update
$tables = [
    'spc_inclusos' => 'contrato',
    'parcelas_em_aberto' => 'contrato',
    'pdd_perdas' => 'codigo_contrato', // check column name
    'pdd_pagos' => 'codigo' // check column name - pdd_pagos has 'codigo' and 'titulo' (venda)
];

// Check actual schema for pdd_pagos and pdd_perdas to be sure
// Based on setup_database.php:
// pdd_perdas: codigo_contrato, codigo_contrato_norm
// pdd_pagos: codigo_norm (from codigo?), titulo_norm (from titulo?)

// Let's do it carefully.

$countTotal = 0;

// 1. SPC Inclusos
echo "Migrating spc_inclusos...\n";
$stmt = $db->query("SELECT id, contrato FROM spc_inclusos");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
$updateStmt = $db->prepare("UPDATE spc_inclusos SET contrato_norm = :norm WHERE id = :id");

$db->beginTransaction();
foreach ($rows as $row) {
    $norm = Normalizer::contrato($row['contrato']);
    $updateStmt->execute([':norm' => $norm, ':id' => $row['id']]);
    $count++;
    if ($count % 1000 == 0) echo "  Processed $count records...\r";
}
$db->commit();
echo "  Done. Updated $count records in spc_inclusos.\n";


// 2. Parcelas Em Aberto
echo "Migrating parcelas_em_aberto...\n";
$stmt = $db->query("SELECT id, contrato FROM parcelas_em_aberto");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
$updateStmt = $db->prepare("UPDATE parcelas_em_aberto SET contrato_norm = :norm WHERE id = :id");

$db->beginTransaction();
foreach ($rows as $row) {
    $norm = Normalizer::contrato($row['contrato']);
    $updateStmt->execute([':norm' => $norm, ':id' => $row['id']]);
    $count++;
    if ($count % 1000 == 0) echo "  Processed $count records...\r";
}
$db->commit();
echo "  Done. Updated $count records in parcelas_em_aberto.\n";


// 3. PDD Perdas
echo "Migrating pdd_perdas...\n";
$stmt = $db->query("SELECT id, codigo_contrato FROM pdd_perdas");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
$updateStmt = $db->prepare("UPDATE pdd_perdas SET codigo_contrato_norm = :norm WHERE id = :id");

$db->beginTransaction();
foreach ($rows as $row) {
    $norm = Normalizer::contrato($row['codigo_contrato']);
    $updateStmt->execute([':norm' => $norm, ':id' => $row['id']]);
    $count++;
}
$db->commit();
echo "  Done. Updated $count records in pdd_perdas.\n";


// 4. PDD Pagos
// Has codigo_norm and titulo_norm
echo "Migrating pdd_pagos...\n";
$stmt = $db->query("SELECT id, codigo, titulo FROM pdd_pagos");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = 0;
$updateStmt = $db->prepare("UPDATE pdd_pagos SET codigo_norm = :cnorm, titulo_norm = :tnorm WHERE id = :id");

$db->beginTransaction();
foreach ($rows as $row) {
    $cnorm = Normalizer::contrato($row['codigo']);
    $tnorm = Normalizer::contrato($row['titulo']); // Reuse contract normalizer for title/venda if it's alphanumeric
    $updateStmt->execute([':cnorm' => $cnorm, ':tnorm' => $tnorm, ':id' => $row['id']]);
    $count++;
}
$db->commit();
echo "  Done. Updated $count records in pdd_pagos.\n";
echo "Migration Complete.\n";
