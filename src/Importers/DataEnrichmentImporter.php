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
        $idxEndereco = -1; // Legacy
        $idxRua = -1;
        $idxNumero = -1;
        $idxBairro = -1;
        $idxCidade = -1;
        $idxUf = -1;
        $idxCep = -1;
        $idxComplemento = -1;
        $idxEnderecoFull = -1;
        
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

                foreach ($rowData as $j => $colName) {
                    if (!$colName) continue;
                    $colName = strtoupper((string)$colName);
                    
                    if (strpos($colName, 'CONTRATO') !== false) $tempContrato = $j;
                }

                if ($tempContrato !== -1) {
                    // Found header row, map all
                    foreach ($rowData as $j => $colName) {
                        if (!$colName) continue;
                        $colName = strtoupper((string)$colName);
                        
                        // Map Contract and Personal Info
                        if (strpos($colName, 'CONTRATO') !== false) $idxContrato = $j;
                        if (strpos($colName, 'NOME') !== false || strpos($colName, 'CONTRATANTE') !== false) $idxNome = $j;
                        if (strpos($colName, 'CPF') !== false || strpos($colName, 'CNPJ') !== false) $idxCpf = $j;
                        
                        // Map Address Components
                        if (strpos($colName, 'RUA') !== false || strpos($colName, 'LOGRADOURO') !== false) $idxRua = $j;
                        if (strpos($colName, 'ENDERECO') !== false) $idxEnderecoFull = $j; // Generic fallback
                        if (strpos($colName, 'NUMERO') !== false || $colName === 'N' || $colName === 'NO') $idxNumero = $j;
                        if (strpos($colName, 'BAIRRO') !== false) $idxBairro = $j;
                        if (strpos($colName, 'CIDADE') !== false || strpos($colName, 'MUNICIPIO') !== false) $idxCidade = $j;
                        if (strpos($colName, 'UF') !== false || strpos($colName, 'ESTADO') !== false) $idxUf = $j;
                        if (strpos($colName, 'CEP') !== false) $idxCep = $j;
                        if (strpos($colName, 'COMPLEMENTO') !== false) $idxComplemento = $j;
                    }
                    $headersFound = true;
                }
                continue; // Done with this row
            }

            // Process Data Row
            $contratoRaw = $rowData[$idxContrato] ?? null;
            if (!$contratoRaw) continue;

            $contratoNorm = Normalizer::contrato($contratoRaw);
            
            $cpf = ($idxCpf !== -1) ? Normalizer::cpfCnpj($rowData[$idxCpf] ?? null) : null;
            $nome = ($idxNome !== -1) ? ($rowData[$idxNome] ?? null) : null;

            // Build Address
            $endereco = null;

            // Priority 1: Full Address Column ("Endereço")
            if ($idxEnderecoFull !== -1 && !empty($rowData[$idxEnderecoFull])) {
                $endereco = $rowData[$idxEnderecoFull];
                
                // Optional: Append specific parts if they might be missing from "Full" address?
                // Usually "Complete Address" implies it has everything. 
                // However, let's append CEP if provided separately and not detected?
                // For now, trust the user's "Endereço Completo".
                
                // If the user wants to append City/UF:
                $extraParts = [];
                if ($idxCidade !== -1 && !empty($rowData[$idxCidade])) $extraParts[] = $rowData[$idxCidade];
                if ($idxUf !== -1 && !empty($rowData[$idxUf])) $extraParts[] = $rowData[$idxUf];
                if ($idxCep !== -1 && !empty($rowData[$idxCep])) $extraParts[] = "CEP: " . $rowData[$idxCep];
                
                if (!empty($extraParts)) {
                    // Check if extra parts are already in the address to avoid duplication (simple check)
                    $enderecoUpper = mb_strtoupper($endereco);
                    foreach ($extraParts as $part) {
                        if (strpos($enderecoUpper, mb_strtoupper($part)) === false) {
                            $endereco .= " - " . $part;
                        }
                    }
                }

            } else {
                // Priority 2: Build from Components
                $addrParts = [];
                if ($idxRua !== -1 && !empty($rowData[$idxRua])) $addrParts[] = $rowData[$idxRua];
                if ($idxNumero !== -1 && !empty($rowData[$idxNumero])) $addrParts[] = $rowData[$idxNumero];
                if ($idxComplemento !== -1 && !empty($rowData[$idxComplemento])) $addrParts[] = $rowData[$idxComplemento];
                if ($idxBairro !== -1 && !empty($rowData[$idxBairro])) $addrParts[] = $rowData[$idxBairro];
                if ($idxCidade !== -1 && !empty($rowData[$idxCidade])) $addrParts[] = $rowData[$idxCidade];
                if ($idxUf !== -1 && !empty($rowData[$idxUf])) $addrParts[] = $rowData[$idxUf];
                if ($idxCep !== -1 && !empty($rowData[$idxCep])) $addrParts[] = "CEP: " . $rowData[$idxCep];
                
                $endereco = !empty($addrParts) ? implode(', ', $addrParts) : null;
            }

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
