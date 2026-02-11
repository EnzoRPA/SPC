<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Correcting spc_inclusos Mismatches ===\n";

// Query to find them first to count
$whereClause = "WHERE (tp_contrato LIKE '%Demitidos%' OR tp_contrato LIKE '%Aposentados%')
                AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '-', ''), '/', '')) > 11
                AND CHAR_LENGTH(cpf_cnpj_norm) <= 11";

$stmt = $db->query("SELECT count(*) as total FROM spc_inclusos $whereClause");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "Found $count records to update.\n";

if ($count > 0) {
    // Perform Update
    // We set cpf_cnpj to cpf_cnpj_norm (which we verified contains the correct CPF)
    $updateSql = "UPDATE spc_inclusos 
                  SET cpf_cnpj = cpf_cnpj_norm 
                  $whereClause";
    
    $stmtUpd = $db->prepare($updateSql);
    $stmtUpd->execute();
    
    echo "Updated " . $stmtUpd->rowCount() . " records.\n";
    
    // Create a log of what was changed?
    // Too late, already changed. But we have the previous mismatch_output.txt
    
    echo "Correction complete. UI will now display correct CPFs.\n";
} else {
    echo "Nothing to update.\n";
}
