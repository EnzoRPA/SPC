<?php
require_once 'config/db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = file_get_contents('database/schema.sql');
    
    // Split by semicolon to execute multiple statements
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        if (trim($statement) != '') {
            $db->exec($statement);
        }
    }
    
    echo "Database schema updated successfully!";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage();
}
