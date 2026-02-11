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
        // Memory optimization: Increase PHP memory limit temporarily
        $oldMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '1024M'); // Increase to 1GB temporarily
        
        // Use createReaderForFile to correctly handle different file types (XLSX, CSV, etc.)
        $reader = IOFactory::createReaderForFile($filePath);
        
        // CRITICAL OPTIMIZATIONS FOR LARGE FILES:
        $reader->setReadDataOnly(true);           // Skip formatting
        $reader->setReadEmptyCells(false);        // Skip empty cells (HUGE memory saver)
        
        // Load spreadsheet
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Disable formula calculations (not needed for data import)
        $spreadsheet->getActiveSheet()->getParent()->getCalculationEngine()->disableCalculationCache();

        // CUSTOM DEBUG LOG FILE
        $debugLog = __DIR__ . '/../../logs/enrichment_debug.log';
        @mkdir(dirname($debugLog), 0777, true);
        $logMsg = function($msg) use ($debugLog) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($debugLog, "[$timestamp] $msg\n", FILE_APPEND);
            error_log($msg);
        };
        
        $logMsg("=== ENRICHMENT IMPORT STARTED ===");
        $logMsg("File: $filePath, Batch: $batchId");

        // Prepare UPDATE statements for BOTH tables (NO INSERTS)
        
        // 1. Update PDD_PERDAS (with individual address components)
        $stmtUpdatePdd = $this->db->prepare("
            UPDATE pdd_perdas 
            SET 
                batch_id = ?, 
                cpf_cnpj = COALESCE(?, cpf_cnpj), 
                endereco = COALESCE(?, endereco),
                nome = COALESCE(?, nome),
                rua = COALESCE(?, rua),
                numero = COALESCE(?, numero),
                bairro = COALESCE(?, bairro),
                cep = COALESCE(?, cep),
                cidade = COALESCE(?, cidade),
                estado = COALESCE(?, estado)
            WHERE codigo_contrato_norm = ?
        ");
        
        // 2. Update SPC_INCLUSOS (also needs cadastral updates including address)
        $stmtUpdateSpc = $this->db->prepare("
            UPDATE spc_inclusos 
            SET 
                batch_id = ?, 
                cpf_cnpj = COALESCE(?, cpf_cnpj),
                contratante = COALESCE(?, contratante),
                rua = COALESCE(?, rua),
                numero = COALESCE(?, numero),
                bairro = COALESCE(?, bairro),
                cep = COALESCE(?, cep),
                cidade = COALESCE(?, cidade),
                estado = COALESCE(?, estado)
            WHERE contrato_norm = ?
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

        $updatedCountPdd = 0;
        $updatedCountSpc = 0;
        
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
                        $colNameUpper = strtoupper((string)$colName);
                        // Remove accents for better matching
                        $colNameClean = str_replace(['Ã', 'Á', 'À', 'Â', 'Ê', 'É', 'Í', 'Ó', 'Ô', 'Õ', 'Ú', 'Ç'], 
                                                   ['A', 'A', 'A', 'A', 'E', 'E', 'I', 'O', 'O', 'O', 'U', 'C'], 
                                                   $colNameUpper);
                        
                        // Map Contract and Personal Info
                        // CRITICAL: Use EXACT match first to avoid TP_CONTRATO being detected as CONTRATO
                        if ($colNameClean === 'CONTRATO') {
                            $idxContrato = $j;
                        } elseif ($idxContrato === -1 && strpos($colNameClean, 'CONTRATO') !== false && strpos($colNameClean, 'TP_') === false && strpos($colNameClean, 'TIPO') === false) {
                            // Fallback: contains CONTRATO but NOT TP_CONTRATO or TIPO_CONTRATO
                            $idxContrato = $j;
                        }
                        
                        // Nome: exact match first, then CONTRATANTE
                        if ($colNameClean === 'NOME') {
                            $idxNome = $j;
                        } elseif ($idxNome === -1 && strpos($colNameClean, 'CONTRATANTE') !== false) {
                            $idxNome = $j;
                        }
                        
                        // CPF: exact matches first, avoid mixing with Nome
                        if ($colNameClean === 'CPF' || $colNameClean === 'CNPJ' || $colNameClean === 'CPF_CNPJ' || $colNameClean === 'CPFCNPJ') {
                            $idxCpf = $j;
                        } elseif ($idxCpf === -1 && $j !== $idxNome && (strpos($colNameClean, 'CPF') !== false || strpos($colNameClean, 'CNPJ') !== false)) {
                            // Only if different from Nome column
                            $idxCpf = $j;
                        }
                        
                        // Map Address Components (MORE FLEXIBLE MATCHING)
                        if (strpos($colNameClean, 'RUA') !== false || 
                            strpos($colNameClean, 'LOGRADOURO') !== false || 
                            strpos($colNameClean, 'ENDERECO') !== false && $idxEnderecoFull === -1) {
                            $idxRua = $j;
                        }
                        
                        // Number variations: NUMERO, Nº, N, NO, NR, NUM
                        if (strpos($colNameClean, 'NUMERO') !== false || 
                            $colNameClean === 'N' || 
                            $colNameClean === 'NO' || 
                            $colNameClean === 'NR' || 
                            $colNameClean === 'NUM' ||
                            preg_match('/^N[ºO]?$/i', $colNameClean)) {
                            $idxNumero = $j;
                        }
                        
                        if (strpos($colNameClean, 'BAIRRO') !== false) $idxBairro = $j;
                        if (strpos($colNameClean, 'CIDADE') !== false || strpos($colNameClean, 'MUNICIPIO') !== false) $idxCidade = $j;
                        if (strpos($colNameClean, 'UF') !== false || strpos($colNameClean, 'ESTADO') !== false) $idxUf = $j;
                        if (strpos($colNameClean, 'CEP') !== false) $idxCep = $j;
                        if (strpos($colNameClean, 'COMPLEMENTO') !== false) $idxComplemento = $j;
                    }
                    $headersFound = true;
                    
                    // DEBUG LOGGING: Show what columns were detected
                    $logMsg("Columns detected:");
                    $logMsg("  Contrato: " . ($idxContrato !== -1 ? "Col $idxContrato" : "NOT FOUND"));
                    $logMsg("  Nome: " . ($idxNome !== -1 ? "Col $idxNome" : "NOT FOUND"));
                    $logMsg("  CPF: " . ($idxCpf !== -1 ? "Col $idxCpf" : "NOT FOUND"));
                    $logMsg("  Rua: " . ($idxRua !== -1 ? "Col $idxRua" : "NOT FOUND"));
                    $logMsg("  Numero: " . ($idxNumero !== -1 ? "Col $idxNumero" : "NOT FOUND"));
                    $logMsg("  Bairro: " . ($idxBairro !== -1 ? "Col $idxBairro" : "NOT FOUND"));
                    $logMsg("  Cidade: " . ($idxCidade !== -1 ? "Col $idxCidade" : "NOT FOUND"));
                    $logMsg("  UF: " . ($idxUf !== -1 ? "Col $idxUf" : "NOT FOUND"));
                    $logMsg("  CEP: " . ($idxCep !== -1 ? "Col $idxCep" : "NOT FOUND"));
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
                // DEBUG: Log what we're about to update
                $logMsg("About to update - Contrato: $contratoRaw (norm: $contratoNorm), CPF: " . ($cpf ?: 'NULL') . ", Nome: " . ($nome ?: 'NULL'));
                
                // Extract individual address components
                $rua = ($idxRua !== -1 && isset($rowData[$idxRua])) ? $rowData[$idxRua] : null;
                $numero = ($idxNumero !== -1 && isset($rowData[$idxNumero])) ? $rowData[$idxNumero] : null;
                $bairro = ($idxBairro !== -1 && isset($rowData[$idxBairro])) ? $rowData[$idxBairro] : null;
                $cep = ($idxCep !== -1 && isset($rowData[$idxCep])) ? $rowData[$idxCep] : null;
                $cidade = ($idxCidade !== -1 && isset($rowData[$idxCidade])) ? $rowData[$idxCidade] : null;
                $uf = ($idxUf !== -1 && isset($rowData[$idxUf])) ? $rowData[$idxUf] : null;
                
                // DEBUG: Log individual address components
                $logMsg("  Address components - Rua: " . ($rua ?: 'NULL') . ", Numero: " . ($numero ?: 'NULL') . ", Bairro: " . ($bairro ?: 'NULL'));
                $logMsg("  Address components - CEP: " . ($cep ?: 'NULL') . ", Cidade: " . ($cidade ?: 'NULL') . ", UF: " . ($uf ?: 'NULL'));
                
                // Update PDD_PERDAS (with individual components + concatenated endereco)
                $stmtUpdatePdd->execute([
                    $batchId, 
                    $cpf ?: null,      
                    $endereco ?: null,  // Concatenated address for backward compatibility
                    $nome ?: null,
                    $rua ?: null,       // Individual components
                    $numero ?: null,
                    $bairro ?: null,
                    $cep ?: null,
                    $cidade ?: null,
                    $uf ?: null,
                    $contratoNorm
                ]);
                $rowsAffected = $stmtUpdatePdd->rowCount();
                $updatedCountPdd += $rowsAffected;
                
                // DEBUG: Log result
                $logMsg("pdd_perdas UPDATE affected $rowsAffected rows for contract $contratoNorm");
                
                // Update SPC_INCLUSOS (CPF, Nome, and individual address components)
                if ($cpf || $nome || $endereco) {
                    // Address components already extracted above, reuse them
                    
                    $stmtUpdateSpc->execute([
                        $batchId,
                        $cpf ?: null,
                        $nome ?: null,
                        $rua ?: null,
                        $numero ?: null,
                        $bairro ?: null,
                        $cep ?: null,
                        $cidade ?: null,
                        $uf ?: null,
                        $contratoNorm
                    ]);
                    $rowsAffectedSpc = $stmtUpdateSpc->rowCount();
                    $updatedCountSpc += $rowsAffectedSpc;
                    
                    // DEBUG: Log result  
                    $logMsg("spc_inclusos UPDATE affected $rowsAffectedSpc rows for contract $contratoNorm");
                }
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
        
        // Log results for debugging
        error_log("DataEnrichmentImporter: Updated $updatedCountPdd records in pdd_perdas, $updatedCountSpc records in spc_inclusos");
        
        // Clean up memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet, $reader);
        
        // Restore original memory limit
        ini_set('memory_limit', $oldMemoryLimit);
    }
}
