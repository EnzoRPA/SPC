<?php
$url = 'postgresql://postgres:G4a1ther2020#@db.ogiwoavudsjlwfkvndgc.supabase.co:5432/postgres';
$parsed = parse_url($url);

echo "URL: $url\n";
echo "Parsed:\n";
print_r($parsed);

if (!isset($parsed['host'])) {
    echo "Host missing! The # broke the parsing.\n";
} else {
    echo "Host found: " . $parsed['host'] . "\n";
}
