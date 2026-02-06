<?php
require 'config/db.php';
use App\Helpers\Normalizer;

$database = new Database();
$db = $database->getConnection();

echo "=== RASTREAMENTO DE PROVA (Espécime Confirmado) ===\n\n";

// 1. Find a contract that IS in both tables
$sqlFind = "SELECT pp.id, pp.codigo_contrato, pp.codigo_contrato_norm 
            FROM pdd_perdas pp 
            JOIN spc_historico_removidos h ON pp.codigo_contrato_norm = h.contrato 
            LIMIT 1";
$specimen = $db->query($sqlFind)->fetch(PDO::FETCH_ASSOC);

if (!$specimen) {
    echo "ERRO CRÍTICO: Não foi possível encontrar NENHUM item que esteja em ambas as tabelas.\n";
    echo "Isso contradiz a contagem anterior. Verifique a lógica de JOIN.\n";
    exit;
}

echo "Espécime Encontrado: ID PDD {$specimen['id']}, Contrato: {$specimen['codigo_contrato']}\n\n";

// 2. Trace execution for this specimen
$contrato = $specimen['codigo_contrato_norm'];

// Check SPC Inclusos
$stmtInc = $db->prepare("SELECT id, data_inclusao, vencimento FROM spc_inclusos WHERE contrato_norm = ?");
$stmtInc->execute([$contrato]);
$inclusos = $stmtInc->fetchAll(PDO::FETCH_ASSOC);

if (count($inclusos) > 0) {
    echo "  [STATUS ATUAL] Está no SPC (Id: {$inclusos[0]['id']}).\n";
} else {
    echo "  [STATUS ATUAL] NÃO está no SPC hoje.\n";
}

// Check Historico
$stmtHist = $db->prepare("SELECT id, data_inclusao_spc, data_remocao, motivo_remocao FROM spc_historico_removidos WHERE contrato = ?");
$stmtHist->execute([$contrato]);
$historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

if (count($historico) > 0) {
    echo "  [HISTÓRICO] Encontrado(s) " . count($historico) . " registro(s) antigo(s).\n";
    foreach ($historico as $hist) {
        echo "    -> Entrou em: {$hist['data_inclusao_spc']} | Saiu em: {$hist['data_remocao']}\n";
        echo "       Motivo: {$hist['motivo_remocao']}\n";
    }
}

// Check Parcelas em Aberto
$stmtOrig = $db->prepare("SELECT id FROM parcelas_em_aberto WHERE contrato_norm = ?");
$stmtOrig->execute([$contrato]);
$origin = $stmtOrig->fetchAll(PDO::FETCH_ASSOC);

if (count($origin) > 0) {
    echo "  [DÍVIDA] Existe em Parcelas em Aberto.\n";
} else {
    echo "  [DÍVIDA] NÃO existe em Parcelas em Aberto (Dívida já baixada ou inexistente).\n";
}
