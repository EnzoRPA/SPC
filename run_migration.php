<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = file_get_contents('database/migration_spc_v2.sql');
    
    // Split by semicolon
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        if (trim($statement) != '') {
            $db->exec($statement);
        }
    }
    
    echo "Migration applied successfully!";
} catch (Exception $e) {
    echo "Error applying migration: " . $e->getMessage();
}
