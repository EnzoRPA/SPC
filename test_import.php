<?php
// Test import to capture exact error
require 'config/db.php';
require 'vendor/autoload.php';

use App\Importer;

$database = new Database();
$db = $database->getConnection();

echo "=== TESTING IMPORT MECHANISM ===\n\n";

// Check if there's a test file in uploads
$uploadsDir = 'uploads/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    $files = array_diff($files, ['.', '..']);
    
    if (count($files) > 0) {
        echo "Files in uploads directory:\n";
        foreach ($files as $file) {
            echo "- $file\n";
        }
        
        // Try to import the first file
        $firstFile = reset($files);
        $filePath = $uploadsDir . $firstFile;
        
        echo "\n=== ATTEMPTING TO IMPORT: $firstFile ===\n";
        
        try {
            $importer = new Importer($db);
            
            // Detect type based on filename
            $type = 'spc'; // default
            if (stripos($firstFile, 'parcela') !== false) {
                $type = 'parcelas';
            } elseif (stripos($firstFile, 'pdd') !== false && stripos($firstFile, 'perda') !== false) {
                $type = 'pdd_perdas';
            } elseif (stripos($firstFile, 'pdd') !== false && stripos($firstFile, 'pago') !== false) {
                $type = 'pdd_pagos';
            }
            
            echo "Detected type: $type\n";
            echo "Importing...\n";
            
            $batchId = $importer->importarArquivo($filePath, $type);
            echo "✓ SUCCESS! Batch ID: $batchId\n";
            
        } catch (Exception $e) {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
        }
    } else {
        echo "No files in uploads directory.\n";
    }
} else {
    echo "Uploads directory does not exist.\n";
}
