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
        // Use createReaderForFile to correctly handle different file types (XLSX, CSV, etc.)
        $reader = IOFactory::createReaderForFile($filePath);
        // optimization: read data only, skip formatting
        $reader->setReadDataOnly(true);
        
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Prepare UPDATE statement (NO INSERTS)
        $stmtUpdate = $this->db->prepare("
            UPDATE pdd_perdas 
            SET 
                batch_id = ?, 
                cpf_cnpj = COALESCE(?, cpf_cnpj), 
                endereco = COALESCE(?, endereco),
                nome = COALESCE(?, nome)
            WHERE codigo_contrato_norm = ?
        ");

        $idxContrato = -1;
        $idxNome = -1;
        $idxCpf = -1;
        $idxEndereco = -1;
        $headersFound = false;

        $updatedCount = 0;
        
        // Iterate row by row to save memory
        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            
            // Get cells for this row
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            // Header Detection (look in first 10 rows to be safe, code said 5 but a bit more buffer helps)
            if (!$headersFound) {
                if ($rowIndex > 10) {
                     // If we went past 10 scan rows and found nothing, probably invalid format or no headers
                     break; 
                }

                $tempContrato = -1;
                $tempCpf = -1;

                foreach ($rowData as $j => $colName) {
                    if (!$colName) continue;
                    $colName = strtoupper((string)$colName);
                    
                    if (strpos($colName, 'CONTRATO') !== false) $tempContrato = $j;
                    if (strpos($colName, 'CPF') !== false || strpos($colName, 'CNPJ') !== false) $tempCpf = $j;
                }

                if ($tempContrato !== -1) {
                    // Found header row, map all
                    foreach ($rowData as $j => $colName) {
                        if (!$colName) continue;
                        $colName = strtoupper((string)$colName);
                        
                        if (strpos($colName, 'CONTRATO') !== false) $idxContrato = $j;
                        if (strpos($colName, 'NOME') !== false || strpos($colName, 'CONTRATANTE') !== false) $idxNome = $j;
                        if (strpos($colName, 'CPF') !== false || strpos($colName, 'CNPJ') !== false) $idxCpf = $j;
                        if (strpos($colName, 'ENDERECO') !== false || strpos($colName, 'RUA') !== false) $idxEndereco = $j;
                    }
                    $headersFound = true;
                }
                continue; // Done with this row (it was either a header or a pre-header row)
            }

            // Process Data Row
            $contratoRaw = $rowData[$idxContrato] ?? null;
            if (!$contratoRaw) continue;

            $contratoNorm = Normalizer::contrato($contratoRaw);
            
            $cpf = ($idxCpf !== -1) ? Normalizer::cpfCnpj($rowData[$idxCpf] ?? null) : null;
            $endereco = ($idxEndereco !== -1) ? ($rowData[$idxEndereco] ?? null) : null;
            $nome = ($idxNome !== -1) ? ($rowData[$idxNome] ?? null) : null;

            if ($cpf || $endereco || $nome) {
                $stmtUpdate->execute([
                    $batchId, 
                    $cpf ?: null,      
                    $endereco ?: null, 
                    $nome ?: null, 
                    $contratoNorm
                ]);
                
                $updatedCount += $stmtUpdate->rowCount();
            }
            
            // Optional: Free up memory for this row cycle if needed, 
            // though variable scope handles most. 
            // PhpSpreadsheet keeps the whole sheet in memory by default though.
            // For strictly large files, we'd need ChunkReadFilter, 
            // but this simple loop refactor is usually enough for 512MB limit unless file is >100k rows.
        }

        if (!$headersFound || $idxContrato === -1) {
             throw new \Exception("Coluna 'Contrato' não encontrada. Impossível vincular dados.");
        }
    }
}
