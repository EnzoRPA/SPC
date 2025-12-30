<?php

try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    echo "Error loading application: " . $e->getMessage();
}
