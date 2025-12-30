-- Tabela de Lotes de Importação
CREATE TABLE IF NOT EXISTS import_batches (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('spc', 'parcelas', 'pdd_perdas', 'pdd_pagos')),
    status VARCHAR(20) DEFAULT 'success' CHECK (status IN ('success', 'error')),
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    records_count INTEGER DEFAULT 0
);

-- Tabela SPC INCLUSOS (Full Refresh)
CREATE TABLE IF NOT EXISTS spc_inclusos (
    id SERIAL PRIMARY KEY,
    batch_id INTEGER,
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
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_spc_cpf_cnpj_norm ON spc_inclusos (cpf_cnpj_norm);
CREATE INDEX IF NOT EXISTS idx_spc_contrato_norm ON spc_inclusos (contrato_norm);
CREATE INDEX IF NOT EXISTS idx_spc_data_vencimento_spc ON spc_inclusos (data_vencimento_spc);

-- Tabela PARCELAS EM ABERTO (Full Refresh)
CREATE TABLE IF NOT EXISTS parcelas_em_aberto (
    id SERIAL PRIMARY KEY,
    batch_id INTEGER,
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
    dias_atraso INTEGER,
    rua VARCHAR(255),
    numero VARCHAR(50),
    bairro VARCHAR(100),
    cep VARCHAR(20),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    -- Colunas Normalizadas
    cpf_cnpj_norm VARCHAR(20),
    contrato_norm VARCHAR(100),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_parcelas_cpf_cnpj_norm ON parcelas_em_aberto (cpf_cnpj_norm);
CREATE INDEX IF NOT EXISTS idx_parcelas_contrato_norm ON parcelas_em_aberto (contrato_norm);
CREATE INDEX IF NOT EXISTS idx_parcelas_vencimento ON parcelas_em_aberto (vencimento);

-- Tabela PDD PERDAS (Append Only)
CREATE TABLE IF NOT EXISTS pdd_perdas (
    id SERIAL PRIMARY KEY,
    batch_id INTEGER,
    -- Colunas Relevantes
    codigo_contrato VARCHAR(100),
    data_vencimento DATE,
    -- Colunas Normalizadas
    codigo_contrato_norm VARCHAR(100),
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pdd_codigo_contrato_norm ON pdd_perdas (codigo_contrato_norm);
CREATE INDEX IF NOT EXISTS idx_pdd_data_vencimento ON pdd_perdas (data_vencimento);

-- Tabela PDD PAGOS (Append Only - Origem PDF)
CREATE TABLE IF NOT EXISTS pdd_pagos (
    id SERIAL PRIMARY KEY,
    batch_id INTEGER,
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
    
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_pagos_codigo_norm ON pdd_pagos (codigo_norm);
CREATE INDEX IF NOT EXISTS idx_pagos_titulo_norm ON pdd_pagos (titulo_norm);
CREATE INDEX IF NOT EXISTS idx_pagos_cpf_cnpj_norm ON pdd_pagos (cpf_cnpj_norm);

-- Tabela Historico Removidos
CREATE TABLE IF NOT EXISTS spc_historico_removidos (
    id SERIAL PRIMARY KEY,
    original_id INTEGER,
    contrato VARCHAR(100),
    tp_contrato VARCHAR(50),
    contratante VARCHAR(255),
    cpf_cnpj VARCHAR(20),
    valor DECIMAL(15, 2),
    vencimento DATE,
    data_inclusao_spc DATE,
    motivo_remocao VARCHAR(255),
    data_remocao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
