# Sistema de Controle SPC

Sistema web para comparação de planilhas de inclusão no SPC e parcelas em aberto.

## 📋 Pré-requisitos

Antes de começar, você precisa ter instalado no seu computador:

1.  **XAMPP** (ou outro servidor local como WAMP/Laragon): Para rodar o PHP e o banco de dados MySQL.
    *   [Baixar XAMPP](https://www.apachefriends.org/pt_br/download.html)
2.  **Composer**: Gerenciador de dependências do PHP (necessário para instalar a biblioteca de Excel).
    *   [Baixar Composer](https://getcomposer.org/download/) (Baixe e instale o `Composer-Setup.exe`)

---

## 🚀 Passo a Passo (Usando Laragon)

Como você está usando o **Laragon**, o processo é bem simples pois ele já traz tudo configurado.

### Passo 1: Iniciar o Laragon
1.  Abra o Laragon.
2.  Clique em **Iniciar Tudo** (Start All).
3.  O Apache e o MySQL devem iniciar.

### Passo 2: Configurar o Banco de Dados
1.  No Laragon, clique no botão **Banco de Dados** (Database) para abrir o HeidiSQL (ou abra o phpMyAdmin se preferir).
2.  Crie uma nova conexão (usuário `root`, senha vazia geralmente).
3.  Crie um novo banco de dados chamado `spc_control`.
4.  Na aba "Consulta" (Query), cole o código abaixo e aperte F9 (Executar):

```sql
CREATE TABLE IF NOT EXISTS import_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    type ENUM('spc', 'parcelas') NOT NULL,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spc_inclusos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    cpf_cnpj VARCHAR(20) NOT NULL,
    nome VARCHAR(255),
    contrato VARCHAR(50),
    data_inclusao DATE,
    valor DECIMAL(10, 2),
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS parcelas_em_aberto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT,
    cpf_cnpj VARCHAR(20) NOT NULL,
    nome VARCHAR(255),
    contrato VARCHAR(50),
    data_vencimento DATE,
    valor DECIMAL(10, 2),
    FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE
);
```

### Passo 3: Instalar Dependências
1.  No Laragon, clique no botão **Terminal** (Cmder).
2.  Navegue até a pasta do projeto:
    ```bash
    cd "C:\Users\enzoverzaro\Documents\SPC ANTI"
    ```
3.  Rode o comando:
    ```bash
    composer install
    ```

### Passo 4: Rodar o Sistema
A maneira mais fácil sem precisar mover a pasta para o `www` do Laragon é usar o servidor embutido do PHP pelo terminal do Laragon:

1.  No Terminal do Laragon (dentro da pasta do projeto), digite:
    ```bash
    php -S localhost:8000 -t public
    ```
2.  Acesse no navegador: `http://localhost:8000`

---

## 📂 Estrutura dos Arquivos de Upload

Para que o sistema funcione corretamente, os arquivos devem seguir a ordem exata das colunas abaixo:

### 1. SPC Inclusos (Excel)
*Substituição Completa*
1.  **CONTRATO**
2.  **TP_CONTRATO**
3.  **CONTRATANTE**
4.  **CONTRATACAO**
5.  **CPF_CNPJ**
6.  **STATUS**
7.  **VENDA**
8.  **PARCELA**
9.  **DEBITO**
10. **EMISSAO**
11. **VENCIMENTO**
12. **DIAS_ATRASO**
13. **RUA**
14. **NUMERO**
15. **BAIRRO**
16. **CEP**
17. **CIDADE**
18. **ESTADO**
19. **Nascimento**
20. **LINHA**
21. **STATUS INCLUSAO**
22. **DATA INCLUSAO**
23. **HORA INCLUSAO**

### 2. Parcelas em Aberto (Excel)
*Substituição Completa*
1.  **CONTRATO**
2.  **TP_CONTRATO**
3.  **CONTRATANTE**
4.  **CONTRATACAO**
5.  **CPF_CNPJ**
6.  **STATUS**
7.  **VENDA**
8.  **PARCELA**
9.  **DEBITO**
10. **EMISSAO**
11. **VENCIMENTO**
12. **DIAS_ATRASO**
13. **RUA**
14. **NUMERO**
15. **BAIRRO**
16. **CEP**
17. **CIDADE**
18. **ESTADO**

### 3. PDD Perdas (Excel)
*Adição (Append)*
O sistema procura automaticamente pelas colunas com os nomes (pode estar em qualquer ordem):
*   **CONTRATO** (ou Código do Contrato)
*   **VENCIMENTO** (ou Data de Vencimento)

### 4. PDD Pagos (PDF)
*Adição (Append)*
O sistema lê PDFs bancários/financeiros procurando por linhas que contenham:
*   Título (ex: `12345-PDD`)
*   Código
*   Cliente
*   CPF/CNPJ
*   Situação
*   Vencimento
*   Valor
