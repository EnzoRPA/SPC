<?php
require_once __DIR__ . '/config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Fixing Collation for spc_ignorados ===\n";

// Fetch collation of parcelas_em_aberto to be sure, or just assume utf8mb4_0900_ai_ci if that's what the error said was missing.
// The error was: Illegal mix of collations (utf8mb4_0900_ai_ci,IMPLICIT) and (utf8mb4_unicode_ci,IMPLICIT)
// This means one side is 0900 and the other is unicode_ci.
// Since spc_ignorados was created with unicode_ci, the others must be 0900.
// So let's align spc_ignorados to utf8mb4_0900_ai_ci.

$sql = "ALTER TABLE spc_ignorados CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;";

try {
    $db->exec($sql);
    echo "Converted spc_ignorados to utf8mb4_0900_ai_ci.\n";
} catch (PDOException $e) {
    // Fallback if 0900 is not supported (older MySQL), but unlikely given the error message mentioning it.
    echo "Error converting: " . $e->getMessage() . "\n";
    // Try utf8mb4_general_ci just in case? Or check result of check_collation.php first?
    // Let's stick with 0900 as per error.
}
