<?php
require_once 'config/db.php';
require_once 'src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

$database = new Database();
$db = $database->getConnection();

// Dados do exemplo da imagem
$contratoAlvo = '2115 PJ'; 
$nomeAlvo = 'Panificadora Ponto Do Pao';

echo "=== Diagnóstico de Inclusão: $nomeAlvo ($contratoAlvo) ===\n\n";

// 1. Normalização
$contratoNorm = Normalizer::contrato($contratoAlvo);
echo "Normalização do Contrato:\n";
echo "Original: '$contratoAlvo' -> Normalizado: '$contratoNorm'\n\n";

// 2. Buscar em Parcelas em Aberto (Busca ampla por nome)
echo "2. Buscando em Parcelas em Aberto (por nome 'Panificadora')...\n";
$stmt = $db->prepare("SELECT * FROM parcelas_em_aberto WHERE contratante LIKE ?");
$stmt->execute(["%Panificadora%"]);
$parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($parcelas)) {
    echo "ERRO: Registro NÃO encontrado em parcelas_em_aberto.\n";
    echo "Isso significa que a importação não pegou este registro ou ele foi limpo.\n";
} else {
    echo "SUCESSO: " . count($parcelas) . " registro(s) encontrado(s).\n";
    foreach ($parcelas as $p) {
        echo " - ID: " . $p['id'] . "\n";
        echo " - Contrato Norm BD: '" . $p['contrato_norm'] . "'\n";
        echo " - CPF/CNPJ Norm BD: '" . $p['cpf_cnpj_norm'] . "'\n";
        echo " - Vencimento: " . $p['vencimento'] . "\n";
        
        // Teste de Filtros
        $venc = $p['vencimento'];
        $fiveYearsAgo = date('Y-m-d', strtotime('-5 years'));
        $passDate = ($venc >= $fiveYearsAgo);
        echo "   -> Filtro Data (> $fiveYearsAgo): " . ($passDate ? "PASS" : "FAIL") . "\n";
        
        $passName = (strpos($p['contratante'], 'Unimed Maranhão Do Sul') !== 0);
        echo "   -> Filtro Nome (Não Unimed): " . ($passName ? "PASS" : "FAIL") . "\n";

        // 3. Cruzamento com SPC Inclusos
        echo "   -> Verificando SPC Inclusos (Bloqueio)...\n";
        $stmtSpc = $db->prepare("SELECT * FROM spc_inclusos WHERE cpf_cnpj_norm = ? AND contrato_norm = ?");
        $stmtSpc->execute([$p['cpf_cnpj_norm'], $p['contrato_norm']]);
        $spc = $stmtSpc->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($spc)) {
            echo "      BLOQUEADO: Encontrado em SPC Inclusos (ID: " . $spc[0]['id'] . ")\n";
        } else {
            echo "      OK: Não está no SPC.\n";
        }

        // 4. Cruzamento com PDD Pagos
        echo "   -> Verificando PDD Pagos (Bloqueio)...\n";
        $stmtPagos = $db->prepare("SELECT * FROM pdd_pagos WHERE codigo_norm = ? OR titulo_norm = ?");
        $stmtPagos->execute([$p['contrato_norm'], $p['contrato_norm']]);
        $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($pagos)) {
            echo "      BLOQUEADO: Encontrado em PDD Pagos (ID: " . $pagos[0]['id'] . ")\n";
        } else {
            echo "      OK: Não está em PDD Pagos.\n";
        }
    }
}
