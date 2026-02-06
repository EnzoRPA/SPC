<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class DataEnrichmentImporter implements ImportStrategy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $cells = [];
            foreach ($cellIterator as $cell) {
                // Read as formatted value to capture dates correctly if needed, or raw
                $cells[] = $cell->getValue();
            }
            $rows[] = $cells;
        }

        if (empty($rows)) {
            return;
        }

        // Detect Headers in first 5 rows
        $idxContrato = -1;
        $idxNome = -1;
        $idxCpf = -1;
        $idxEndereco = -1;
        
        // Helper to find headers
        $headerRowIndex = 0;
        foreach ($rows as $i => $row) {
            if ($i > 5) break; 
            
            $tempContrato = -1;
            $tempCpf = -1;
            
            foreach ($row as $j => $colName) {
                if (!$colName) continue;
                $colName = strtoupper((string)$colName);
                
                if (strpos($colName, 'CONTRATO') !== false) $tempContrato = $j;
                if (strpos($colName, 'CPF') !== false || strpos($colName, 'CNPJ') !== false) $tempCpf = $j;
            }
            
            if ($tempContrato !== -1) {
                $headerRowIndex = $i;
                // Found header row, map all
                foreach ($row as $j => $colName) {
                    if (!$colName) continue;
                    $colName = strtoupper((string)$colName);
                    
                    if (strpos($colName, 'CONTRATO') !== false) $idxContrato = $j;
                    if (strpos($colName, 'NOME') !== false || strpos($colName, 'CONTRATANTE') !== false) $idxNome = $j;
                    if (strpos($colName, 'CPF') !== false || strpos($colName, 'CNPJ') !== false) $idxCpf = $j;
                    if (strpos($colName, 'ENDERECO') !== false || strpos($colName, 'RUA') !== false) $idxEndereco = $j;
                }
                break;
            }
        }

        if ($idxContrato === -1) {
            throw new \Exception("Coluna 'Contrato' não encontrada. Impossível vincular dados.");
        }

        // Prepare UPDATE statement (NO INSERTS)
        // Only update fields if they satisfy conditions (e.g. overwrite or fill empty)
        // User requested: "concatenar/deixar mais completos". We will prioritize non-empty values from file.
        $stmtUpdate = $this->db->prepare("
            UPDATE pdd_perdas 
            SET 
                batch_id = ?, -- Update batch ID to track latest touch? Optional.
                cpf_cnpj = COALESCE(?, cpf_cnpj), 
                endereco = COALESCE(?, endereco),
                nome = COALESCE(?, nome)
            WHERE codigo_contrato_norm = ?
        ");

        $updatedCount = 0;

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) continue; // Skip headers

            $contratoRaw = $row[$idxContrato] ?? null;
            if (!$contratoRaw) continue;

            $contratoNorm = Normalizer::contrato($contratoRaw);
            
            $cpf = ($idxCpf !== -1) ? Normalizer::cpfCnpj($row[$idxCpf] ?? null) : null;
            $endereco = ($idxEndereco !== -1) ? ($row[$idxEndereco] ?? null) : null;
            $nome = ($idxNome !== -1) ? ($row[$idxNome] ?? null) : null;

            // Only proceed if we have at least one useful field to update
            if ($cpf || $endereco || $nome) {
                // COALESCE logic in SQL handles "Keep existing if new is null"
                // But we want "Overwrite existing with New if New is NOT null"
                // The SQL `COALESCE(?, cpf_cnpj)` means: If ? (new value) is NOT NULL, use it. Else use existing.
                // However, we passed nulls from PHP if empty. So logic holds:
                // If spreadsheet has value -> Update. If spreadsheet empty -> Keep DB value.
                
                // EXECUTE
                $stmtUpdate->execute([
                    $batchId, 
                    $cpf ?: null,      // Ensure PHP null if empty/false
                    $endereco ?: null, 
                    $nome ?: null, 
                    $contratoNorm
                ]);
                
                $updatedCount += $stmtUpdate->rowCount();
            }
        }
        
        // Log or return result? (Optional, handled by caller mostly)
    }
}
