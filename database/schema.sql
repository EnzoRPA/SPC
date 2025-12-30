CREATE DATABASE IF NOT EXISTS spc_control;
USE spc_control;

-- Tabela de Lotes de Importação
CREATE TABLE IF NOT EXISTS import_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    type ENUM('spc', 'parcelas', 'pdd_perdas', 'pdd_pagos') NOT NULL,
    status ENUM('success', 'error') DEFAULT 'success',
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    records_count INT DEFAULT 0
);

-- Tabela SPC INCLUSOS (Full Refresh)
CREATE TABLE IF NOT EXISTS spc_inclusos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    -- Colunas Originais
    tipo_documento VARCHAR(50),
    cpf_cnpj VARCHAR(20),
    contrato VARCHAR(100),
    data_vencimento_spc DATE,
    valor_debito DECIMAL(15, 2),
    data_inclusao DATE,
    data_disponivel DATE,
    prescrito VARCHAR(10),
    data_compra DATE,
    -- Colunas Normalizadas
    cpf_cnpj_norm VARCHAR(20),
    contrato_norm VARCHAR(100),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    INDEX (cpf_cnpj_norm),
    INDEX (contrato_norm),
    INDEX (data_vencimento_spc)
);

-- Tabela PARCELAS EM ABERTO (Full Refresh)
CREATE TABLE IF NOT EXISTS parcelas_em_aberto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    -- Colunas Originais
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
    -- Colunas Normalizadas
    cpf_cnpj_norm VARCHAR(20),
    contrato_norm VARCHAR(100),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    INDEX (cpf_cnpj_norm),
    INDEX (contrato_norm),
    INDEX (vencimento)
);

-- Tabela PDD PERDAS (Append Only)
CREATE TABLE IF NOT EXISTS pdd_perdas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    -- Colunas Relevantes
    codigo_contrato VARCHAR(100),
    data_vencimento DATE,
    -- Colunas Normalizadas
    codigo_contrato_norm VARCHAR(100),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    INDEX (codigo_contrato_norm),
    INDEX (data_vencimento)
);

-- Tabela PDD PAGOS (Append Only - Origem PDF)
CREATE TABLE IF NOT EXISTS pdd_pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    -- Dados Extraídos
    titulo VARCHAR(100),
    codigo VARCHAR(100),
    cliente VARCHAR(255),
    cpf_cnpj VARCHAR(20),
    situacao VARCHAR(50),
    vencimento_boleto DATE,
    valor_titulo DECIMAL(15, 2),
    -- Colunas Normalizadas
    codigo_norm VARCHAR(100), -- Sem zeros à esquerda
    titulo_norm VARCHAR(100), -- Sem sufixo -PDD
    cpf_cnpj_norm VARCHAR(20),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    INDEX (codigo_norm),
    INDEX (titulo_norm),
    INDEX (cpf_cnpj_norm)
);
