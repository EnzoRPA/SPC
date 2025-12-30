<?php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$cpf = '00465548324';
$contrato = '26892';

echo "=== ANALYZING EXCLUSION CANDIDATE: CPF $cpf | Contrato $contrato ===\n";

// 1. Check exact record in spc_inclusos
$stmt = $db->prepare("SELECT * FROM spc_inclusos WHERE cpf_cnpj_norm LIKE ? AND contrato_norm LIKE ?");
$stmt->execute(["%$cpf%", "%$contrato%"]);
$inclusos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "In SPC Inclusos (Count: " . count($inclusos) . "):\n";
foreach($inclusos as $r) {
    echo "ID: {$r['id']}, CPF: {$r['cpf_cnpj']} (Norm: {$r['cpf_cnpj_norm']}), Contrato: {$r['contrato']} (Norm: {$r['contrato_norm']})\n";
}

// 2. Search in parcelas_em_aberto (Broad Search)
echo "\nSearching in Parcelas Em Aberto (Broad Search)...\n";
$stmt = $db->prepare("SELECT * FROM parcelas_em_aberto WHERE cpf_cnpj LIKE ? OR contrato LIKE ?");
$stmt->execute(["%$cpf%", "%$contrato%"]);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($parcelas)) {
    echo "NO MATCH FOUND in Parcelas Em Aberto.\n";
} else {
    echo "Matches found in Parcelas Em Aberto (Count: " . count($parcelas) . "):\n";
    foreach($parcelas as $r) {
        echo "ID: {$r['id']}, CPF: {$r['cpf_cnpj']} (Norm: {$r['cpf_cnpj_norm']}), Contrato: {$r['contrato']} (Norm: {$r['contrato_norm']})\n";
        
        // Check equality
        $cpfMatch = ($r['cpf_cnpj_norm'] == $inclusos[0]['cpf_cnpj_norm'] ?? 'N/A') ? 'YES' : 'NO';
        $conMatch = ($r['contrato_norm'] == $inclusos[0]['contrato_norm'] ?? 'N/A') ? 'YES' : 'NO';
        echo "  -> Matches Inclusos Norm? CPF: $cpfMatch, Contract: $conMatch\n";
    }
}
