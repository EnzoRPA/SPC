<?php
$uploadDir = __DIR__ . '/public/uploads';
$files = glob($uploadDir . '/*.xlsx');
foreach ($files as $file) {
    echo "Found: " . $file . "\n";
    if (file_exists($file)) {
        echo "  Exists!\n";
    } else {
        echo "  Does not exist (PHP says)!\n";
    }
}
