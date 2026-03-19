<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class PddPagosImporter implements ImportStrategy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        $log = "=== IMPORT START: " . date('Y-m-d H:i:s') . " ===\n";
        $log .= "File: $filePath\n";
        
        $lines = [];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($ext === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            $lines = explode("\n", $text);
            $log .= "Parsed PDF Context\n";
        } else {
            // Read Excel single column format
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, false, false);
            
            // Convert to array of strings (flatten column A)
            foreach ($rows as $row) {
                if (!empty($row[0])) {
                    $lines[] = trim($row[0]);
                }
            }
            $log .= "Parsed Excel Context (Single Column)\n";
        }
        
        $stmtCheck = $this->db->prepare("SELECT id FROM pdd_pagos WHERE codigo_norm = ? AND titulo_norm = ? LIMIT 1");
        $stmtInsert = $this->db->prepare("
            INSERT INTO pdd_pagos (
                batch_id, titulo, codigo, cliente, cpf_cnpj, situacao, vencimento_boleto, valor_titulo,
                codigo_norm, titulo_norm, cpf_cnpj_norm
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $matchesCount = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $matched = false;
            $titulo = $codigo = $cliente = $cpf = $situacao = $vencimento = $valorTitulo = null;
            
            // Standard PDF pattern
            // /(.+?)\s+(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})\s+(\S+)\s+([\d\.,]+)\s+(\d{2}\/\d{2}\/\d{4})([A-Z0-9]+)\s+([\d\.]*,\d{2})(\d+-PDD)/
            if (preg_match('/(.+?)\s+(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})\s+(\S+)\s+([\d\.,]+)\s+(\d{2}\/\d{2}\/\d{4})([A-Z0-9]+)\s+([\d\.]*,\d{2})(\d+-PDD)/', $line, $matches)) {
                $cliente = trim($matches[1]);
                $cpf = $matches[2];
                $situacao = $matches[3];
                // $valor1 = Normalizer::valor($matches[4]); // Ignored in PDF normally
                $vencimento = Normalizer::data($matches[5]);
                $codigo = $matches[6];
                // $valor2 = Normalizer::valor($matches[7]); // Ignored in PDF normally
                $valorTitulo = Normalizer::valor($matches[4]); // Using the first value as reference, or 7. Actually code used $matches[4] for valorTitulo
                $tituloRaw = $matches[8];
                $titulo = str_replace('-PDD', '', $tituloRaw);
                $matched = true;
            } 
            // Single Column Excel Pattern
            // /^(\S+)\s+(\d+)\s+(.+?)\s+(\d{2,3}\.\d{3}\.\d{3}\/?\d*-\d{2})\s+(\S+)\s+(\d{2}\/\d{2}\/\d{4})\s+([\d.,]+)\s+([\d.,]+)$/
            elseif (preg_match('/^(\S+)\s+(\d+)\s+(.+?)\s+(\d{2,3}\.\d{3}\.\d{3}\/?\d*-\d{2})\s+(\S+)\s+(\d{2}\/\d{2}\/\d{4})\s+([\d.,]+)\s+([\d.,]+)$/', $line, $matches)) {
                $tituloRaw = $matches[1];
                $codigo = $matches[2];
                $cliente = trim($matches[3]);
                $cpf = $matches[4];
                $situacao = $matches[5];
                $vencimento = Normalizer::data($matches[6]);
                $valorTitulo = Normalizer::valor($matches[8]); // Using the last value
                
                $titulo = str_replace('-PDD', '', $tituloRaw);
                $matched = true;
            }

            if ($matched) {
                $matchesCount++;
                $codigoNorm = ltrim($codigo, '0');
                $tituloNorm = $titulo;
                $cpfNorm = Normalizer::cpfCnpj($cpf);
                
                $stmtCheck->execute([$codigoNorm, $tituloNorm]);
                if (!$stmtCheck->fetch()) {
                    $stmtInsert->execute([
                        $batchId, $titulo, $codigo, $cliente, $cpf, $situacao, $vencimento, $valorTitulo,
                        $codigoNorm, $tituloNorm, $cpfNorm
                    ]);
                }
            } else {
                if (strpos($line, 'PDD') !== false || preg_match('/\d{3}\.\d{3}\.\d{3}/', $line)) {
                    $log .= "FAILED MATCH: $line\n";
                }
            }
        }
        
        $log .= "Total Matches: $matchesCount\n";
        file_put_contents('debug_pdd_pagos.log', $log, FILE_APPEND);
    }
}
