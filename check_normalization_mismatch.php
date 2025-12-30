<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

$database = new Database();
$db = $database->getConnection();

echo "Checking for mismatch between Parcelas and SPC Inclusos...\n";

// Query to find mismatches where Normalized CPF matches but Normalized Contract differs
// This is the "Ghost" scenario
$sql = "
    SELECT 
        p.contrato as p_contrato, 
        s.contrato as s_contrato,
        p.contrato_norm as p_norm,
        s.contrato_norm as s_norm,
        p.cpf_cnpj as p_cpf,
        s.cpf_cnpj as s_cpf,
        p.id as parc_id,
        s.id as spc_id
    FROM parcelas_em_aberto p
    JOIN spc_inclusos s ON p.cpf_cnpj_norm = s.cpf_cnpj_norm
    WHERE p.contrato_norm != s.contrato_norm
    -- Try to match by stripping zeros manually in SQL to see if they WOULD match
    AND TRIM(LEADING '0' FROM p.contrato_norm) = TRIM(LEADING '0' FROM s.contrato_norm)
    LIMIT 20
";

$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) > 0) {
    echo "Found " . count($results) . " mismatches where Contract differs only by LEADING ZEROS:\n\n";
    foreach ($results as $row) {
        echo "CPF: " . $row['p_cpf'] . "\n";
        echo "  Parcelas Contract: '" . $row['p_contrato'] . "' (Norm: '" . $row['p_norm'] . "')\n";
        echo "  SPC Contract:      '" . $row['s_contrato'] . "' (Norm: '" . $row['s_norm'] . "')\n";
        echo "--------------------------------------------------\n";
    }
} else {
    echo "No obvious leading-zero mismatches found via SQL check.\n";
}

echo "\n--- Local Normalizer Test ---\n";
$tests = ['00123', '123', '000555', '555', '012300'];
foreach ($tests as $t) {
    echo "original: '$t' -> norm: '" . Normalizer::contrato($t) . "'\n";
}
