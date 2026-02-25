<?php
$file = 'logs/enrichment_debug.log';
$bytes = 20000;

if (!file_exists($file)) {
    echo "Log file not found.";
    exit;
}

$fp = fopen($file, 'r');
fseek($fp, -$bytes, SEEK_END);
echo fread($fp, $bytes);
fclose($fp);
