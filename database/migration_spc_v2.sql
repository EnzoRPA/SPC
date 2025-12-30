-- Drop table to recreate with new structure
DROP TABLE IF EXISTS spc_inclusos;

CREATE TABLE spc_inclusos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    -- Novas Colunas (Baseadas na imagem)
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
    
    -- Colunas Normalizadas (Mantidas para comparação)
    cpf_cnpj_norm VARCHAR(20),
    contrato_norm VARCHAR(100),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
    INDEX (cpf_cnpj_norm),
    INDEX (contrato_norm),
    INDEX (vencimento)
);
