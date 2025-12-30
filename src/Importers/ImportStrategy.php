<?php

namespace App\Importers;

interface ImportStrategy {
    public function import($filePath, $batchId);
}
