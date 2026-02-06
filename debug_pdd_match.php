<?php
require 'config/db.php';
use App\Helpers\Normalizer;

$database = new Database();
$db = $database->getConnection();

echo "=== CHECKING PDD PERDAS vs HISTORICO REMOVIDOS MATCH ===\n\n";

echo "--- Sample from spc_historico_removidos ---\n";
try {
    $stmt = $db->query("SELECT id, contrato, motivo_remocao FROM spc_historico_removidos LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Contrato: [{$row['contrato']}], Motivo: {$row['motivo_remocao']}\n";
    }

    $sql = "SELECT COUNT(*) as count 
            FROM spc_historico_removidos h
            JOIN pdd_perdas pp ON h.contrato = pp.codigo_contrato_norm";
    $countHist = $db->query($sql)->fetchColumn();
    echo "Matches in Historico (pdd.contrato_norm = hist.contrato): $countHist\n";
    
    $stmt = $db->query("DESCRIBE spc_historico_removidos");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Historico Columns: " . implode(", ", $cols) . "\n";
    
} catch (Exception $e) {
    echo "Error checking historico: " . $e->getMessage() . "\n";
}

echo "\n--- Conclusion ---\n";
$sql = "SELECT COUNT(*) FROM pdd_perdas";
$total = $db->query($sql)->fetchColumn();
echo "Total PDD: $total. Matches in Historico: " . ($countHist ?? 0) . "\n";
