<?php
// Test the actual import process
require 'config/db.php';
require 'vendor/autoload.php';

use App\Importer;

$database = new Database();
$db = $database->getConnection();

echo "=== TESTING SPC IMPORT ===\n\n";

$testFile = 'public/uploads/test_spc.xlsx';

if (!file_exists($testFile)) {
    echo "✗ Test file not found: $testFile\n";
    exit(1);
}

echo "✓ Test file exists: $testFile\n";

try {
    $importer = new Importer($db);
    echo "✓ Importer instantiated\n";
    
    echo "\nAttempting to import...\n";
    $batchId = $importer->importarArquivo($testFile, 'spc');
    
    echo "✓ SUCCESS! Import completed\n";
    echo "  Batch ID: $batchId\n";
    
    // Check how many rows were imported
    $stmt = $db->query("SELECT COUNT(*) as count FROM spc_inclusos");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Rows imported: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "\n✗ IMPORT FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
