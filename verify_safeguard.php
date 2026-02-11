<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Comparator.php';

use App\Comparator;

$database = new Database();
$db = $database->getConnection();
$comparator = new Comparator($db);

// 1. Clean up any previous test data
$db->exec("DELETE FROM parcelas_em_aberto WHERE contrato = 'TEST_DEMITIDO_BAD'");

// 2. Insert a BAD record (Demitido with CNPJ)
try {
    $stmt = $db->prepare("INSERT INTO parcelas_em_aberto (
        batch_id, contrato, tp_contrato, contratante, contratacao, cpf_cnpj, status, venda, parcela, debito, emissao, vencimento, dias_atraso, rua, numero, bairro, cep, cidade, estado, cpf_cnpj_norm, contrato_norm
    ) VALUES (
        148, 'TEST_DEMITIDO_BAD', 'Demitidos / Aposentados', 'Test Bad Company', '2023-01-01', '12.345.678/0001-99', 'Em Aberto', 'V123', '1', '100.00', '2023-01-01', '2023-02-01', 100, 'Rua Teste', '123', 'Bairro', '12345678', 'Cidade', 'UF', '12345678000199', 'TESTDEMITIDOBAD'
    )");
    $stmt->execute();
} catch (PDOException $e) {
    echo "INSERT FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Call obterParaInclusao
echo "Checking inclusion list for BAD record...\n";
$inclusao = $comparator->obterParaInclusao();

$foundBad = false;
foreach ($inclusao as $item) {
    if ($item['contrato'] === 'TEST_DEMITIDO_BAD') {
        $foundBad = true;
        break;
    }
}

if ($foundBad) {
    echo "FAIL: Record 'TEST_DEMITIDO_BAD' (CNPJ) appeared in inclusion list!\n";
} else {
    echo "PASS: Record 'TEST_DEMITIDO_BAD' (CNPJ) was correctly filtered out.\n";
}

// 4. Cleanup
$db->exec("DELETE FROM parcelas_em_aberto WHERE contrato = 'TEST_DEMITIDO_BAD'");
