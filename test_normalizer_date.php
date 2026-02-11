<?php

require __DIR__ . '/vendor/autoload.php';
// require __DIR__ . '/src/Helpers/Normalizer.php'; // already autoloaded? No, let's include it to be safe if not in autoload
require_once __DIR__ . '/src/Helpers/Normalizer.php';

use App\Helpers\Normalizer;

$inputs = [
    215437,
    '215437',
    '30/07/215437',
    '215437-07-30',
    '7/30/215437', // US format?
    '30-07-215437',
    45000, 
    '01/01/2025',
    '30/07/2154'
];

$output = "";
foreach ($inputs as $input) {
    try {
        $result = Normalizer::data($input);
        $output .= "Input: " . var_export($input, true) . " => Output: " . var_export($result, true) . "\n";
    } catch (\Throwable $e) {
        $output .= "Input: " . var_export($input, true) . " => Error: " . $e->getMessage() . "\n";
    }
}

file_put_contents('test_output.txt', $output);
echo "Done.\n";
