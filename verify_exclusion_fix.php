<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Comparator.php';

use App\Comparator;

$database = new Database();
$db = $database->getConnection();
$comparator = new Comparator($db);

echo "Running obtenirParaExclusao()...\n";
$exclusao = $comparator->obterParaExclusao();

$found = false;
foreach ($exclusao as $item) {
    if ($item['contrato'] == '63818') {
        $found = true;
        echo "Contract 63818 STILL FOUND in exclusion list!\n";
        print_r($item);
    }
}

if (!$found) {
    echo "Contract 63818 NOT found in exclusion list. Fix successful!\n";
}
