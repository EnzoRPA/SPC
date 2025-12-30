<?php
header('Content-Type: text/plain');

echo "Debug Environment\n";
echo "=================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "User: " . get_current_user() . "\n";

echo "\nCheck Specific Keys (getenv):\n";
$keys = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_PORT', 'DB_CONNECTION'];
foreach ($keys as $key) {
    $val = getenv($key);
    echo "$key: " . ($val === false ? '(false)' : ($val === '' ? '(empty)' : $val)) . "\n";
}

echo "\nCheck Specific Keys (\$_ENV):\n";
foreach ($keys as $key) {
    $val = $_ENV[$key] ?? '(missing)';
    echo "$key: " . $val . "\n";
}

echo "\nComplete \$_ENV Keys:\n";
print_r(array_keys($_ENV));

echo "\nComplete \$_SERVER Keys:\n";
print_r(array_keys($_SERVER));
