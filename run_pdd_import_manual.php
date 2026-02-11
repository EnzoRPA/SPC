<?php
require 'vendor/autoload.php';
require 'config/db.php';
// Autoloader should theoretically handle checking src/, but let's be explicit if needed
// or just rely on vendor/autoload.php if 'App\\' is mapped to 'src/'

// If composer.json maps "App\\" to "src/", then:
use App\Importers\PddPerdasImporter;


$db = (new Database())->getConnection();
$importer = new PddPerdasImporter($db);

$filePath = __DIR__ . '/arquivos Upados/INCLUIR - 26-11-2025.xlsx';

if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

echo "Starting import for: $filePath\n";
try {
    // Using a dummy batch ID for this manual run, or we could look up the last one
    $batchId = 'manual_fix_' . date('Ymd_His');
    $importer->import($filePath, $batchId);
    echo "Import completed successfully!\n";
} catch (Exception $e) {
    echo "Error during import: " . $e->getMessage() . "\n";
}

// Check results
$stmt = $db->query("SELECT count(*) as total, sum(case when valor > 0 then 1 else 0 end) as with_value FROM pdd_perdas");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Result Stats: Total Rows: {$stats['total']}, Rows with Value > 0: {$stats['with_value']}\n";
