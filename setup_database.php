<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Import Batches
    $db->exec("DROP TABLE IF EXISTS import_batches");
    $db->exec("CREATE TABLE import_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255),
        type VARCHAR(50),
        status VARCHAR(50) DEFAULT 'success',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. SPC Inclusos (Nova Estrutura Completa)
    $db->exec("DROP TABLE IF EXISTS spc_inclusos");
    $db->exec("CREATE TABLE spc_inclusos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        contrato VARCHAR(100),
        tp_contrato VARCHAR(50),
        contratante VARCHAR(255),
        contratacao DATE,
        cpf_cnpj VARCHAR(20),
        status VARCHAR(50),
        venda VARCHAR(50),
        parcela VARCHAR(20),
        debito DECIMAL(15, 2),
        emissao DATE,
        vencimento DATE,
        dias_atraso INT,
        rua VARCHAR(255),
        numero VARCHAR(50),
        bairro VARCHAR(100),
        cep VARCHAR(20),
        cidade VARCHAR(100),
        estado VARCHAR(2),
        nascimento DATE,
        linha VARCHAR(50),
        status_inclusao VARCHAR(50),
        data_inclusao DATE,
        hora_inclusao TIME,
        cpf_cnpj_norm VARCHAR(20),
        contrato_norm VARCHAR(100),
        FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
        INDEX (cpf_cnpj_norm),
        INDEX (contrato_norm)
    )");

    // 3. Parcelas em Aberto
    $db->exec("DROP TABLE IF EXISTS parcelas_em_aberto");
    $db->exec("CREATE TABLE parcelas_em_aberto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        contrato VARCHAR(100),
        tp_contrato VARCHAR(50),
        contratante VARCHAR(255),
        contratacao DATE,
        cpf_cnpj VARCHAR(20),
        status VARCHAR(50),
        venda VARCHAR(50),
        parcela VARCHAR(20),
        debito DECIMAL(15, 2),
        emissao DATE,
        vencimento DATE,
        dias_atraso INT,
        rua VARCHAR(255),
        numero VARCHAR(50),
        bairro VARCHAR(100),
        cep VARCHAR(20),
        cidade VARCHAR(100),
        estado VARCHAR(2),
        cpf_cnpj_norm VARCHAR(20),
        contrato_norm VARCHAR(100),
        FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
        INDEX (cpf_cnpj_norm),
        INDEX (contrato_norm)
    )");

    // 4. PDD Perdas
    $db->exec("CREATE TABLE IF NOT EXISTS pdd_perdas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        codigo_venda VARCHAR(100),
        codigo_contrato VARCHAR(100),
        data_vencimento DATE,
        codigo_contrato_norm VARCHAR(100),
        FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
        INDEX (codigo_contrato_norm)
    )");

    // 5. PDD Pagos
    $db->exec("CREATE TABLE IF NOT EXISTS pdd_pagos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        titulo VARCHAR(100),
        codigo VARCHAR(100),
        cliente VARCHAR(255),
        cpf_cnpj VARCHAR(50),
        situacao VARCHAR(50),
        vencimento_boleto DATE,
        valor_titulo DECIMAL(15, 2),
        codigo_norm VARCHAR(100),
        titulo_norm VARCHAR(100),
        cpf_cnpj_norm VARCHAR(20),
        FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
        INDEX (codigo_norm),
        INDEX (titulo_norm)
    )");

    // 6. SPC Excluídos (Blacklist)
    $db->exec("DROP TABLE IF EXISTS spc_excluidos");
    $db->exec("CREATE TABLE spc_excluidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        cpf_cnpj VARCHAR(20),
        contrato VARCHAR(100),
        vencimento DATE,
        data_exclusao DATE,
        cpf_cnpj_norm VARCHAR(20),
        contrato_norm VARCHAR(100),
        FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
        INDEX (cpf_cnpj_norm),
        INDEX (contrato_norm),
        INDEX (vencimento)
    )");

    echo "Banco de dados atualizado com sucesso (Tabelas recriadas)!";

} catch (Exception $e) {
    echo "Erro ao configurar banco de dados: " . $e->getMessage();
}
