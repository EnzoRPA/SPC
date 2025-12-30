<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->exec("CREATE TABLE IF NOT EXISTS spc_historico_removidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT,
        contrato VARCHAR(100),
        tp_contrato VARCHAR(50),
        contratante VARCHAR(255),
        cpf_cnpj VARCHAR(20),
        valor DECIMAL(15, 2),
        vencimento DATE,
        data_inclusao_spc DATE,
        data_remocao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        motivo_remocao VARCHAR(255),
        usuario_remocao VARCHAR(100) DEFAULT 'Sistema',
        INDEX (contrato),
        INDEX (cpf_cnpj)
    )");

    echo "Tabela spc_historico_removidos criada com sucesso!";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
