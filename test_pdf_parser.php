<?php
require 'vendor/autoload.php';

try {
    $parser = new \Smalot\PdfParser\Parser();
    echo "PDF Parser class loaded successfully.\n";
} catch (Throwable $e) {
    echo "Error loading PDF Parser: " . $e->getMessage() . "\n";
}
