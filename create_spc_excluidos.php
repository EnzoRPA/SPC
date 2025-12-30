<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 6. SPC Excluídos (Blacklist)
    $db->exec("CREATE TABLE IF NOT EXISTS spc_excluidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT,
        cpf_cnpj VARCHAR(20),
        contrato VARCHAR(100),
        data_exclusao DATE,
        cpf_cnpj_norm VARCHAR(20),
        contrato_norm VARCHAR(100),
        FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
        INDEX (cpf_cnpj_norm),
        INDEX (contrato_norm)
    )");

    echo "Tabela spc_excluidos criada com sucesso!";

} catch (Exception $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
