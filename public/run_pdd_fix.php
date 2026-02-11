<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../vendor/autoload.php';
require '../config/db.php';

use App\Importers\PddPerdasImporter;

$db = (new Database())->getConnection();
$importer = new PddPerdasImporter($db);

$fileName = 'INCLUIR - 26-11-2025.xlsx';
$filePath = __DIR__ . '/../arquivos Upados/' . $fileName;

echo "<h1>PDD Fix Run</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Zip Extension: " . (extension_loaded('zip') ? 'YES' : 'NO') . "</p>";

if (!file_exists($filePath)) {
    die("File not found: $filePath");
}

echo "<p>Starting import for: $fileName</p>";
echo "<pre>";

try {
    $batchId = 'manual_fix_web_' . date('Ymd_His');
    $importer->import($filePath, $batchId);
    echo "Import completed successfully!\n";
} catch (Exception $e) {
    echo "Error during import: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

// Check results
$stmt = $db->query("SELECT count(*) as total, sum(case when valor > 0 then 1 else 0 end) as with_value FROM pdd_perdas");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nResult Stats: Total Rows: {$stats['total']}, Rows with Value > 0: {$stats['with_value']}\n";
echo "</pre>";
