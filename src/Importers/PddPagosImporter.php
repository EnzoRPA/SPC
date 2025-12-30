<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use Smalot\PdfParser\Parser;
use PDO;

class PddPagosImporter implements ImportStrategy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        // Append Only
        // PDD Pagos (PDF PF e PDF PJ)
        // Campos: TITULO, CODIGO, CLIENTE, CNPJ/CPF, SIT., VENC., VALOR TIT., VALOR
        // Regras: remover zeros à esquerda de CODIGO; em TITULO remover o sufixo “-PDD”
        
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        
        // Vamos tentar extrair linha a linha ou usar regex.
        // O layout de PDF bancário/financeiro costuma ser tabular.
        // Vamos assumir que o texto extraído mantém a quebra de linha.
        
        $lines = explode("\n", $text);
        
        // DEBUG LOGGING
        $log = "=== IMPORT START: " . date('Y-m-d H:i:s') . " ===\n";
        $log .= "File: $filePath\n";
        $log .= "Total Text Length: " . strlen($text) . "\n";
        $log .= "First 500 chars:\n" . substr($text, 0, 500) . "\n\n";
        
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

            // Regex ajustada para separar Valor (com 2 casas decimais) do Título.
            // O valor sempre termina em ,XX (ex: 21,47). O título vem grudado logo depois.
            // Usamos ([\d\.]*,\d{2}) para capturar o valor exato.
            
            if (preg_match('/(.+?)\s+(\d{3}\.\d{3}\.\d{3}-\d{2}|\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})\s+(\S+)\s+([\d\.,]+)\s+(\d{2}\/\d{2}\/\d{4})([A-Z0-9]+)\s+([\d\.]*,\d{2})(\d+-PDD)/', $line, $matches)) {
                $matchesCount++;
                $cliente = trim($matches[1]);
                $cpf = $matches[2];
                $situacao = $matches[3];
                $valor1 = Normalizer::valor($matches[4]);
                $vencimento = Normalizer::data($matches[5]);
                $codigo = $matches[6];
                $valor2 = Normalizer::valor($matches[7]);
                $tituloRaw = $matches[8];
                
                $valorTitulo = $valor1;
                $valorPago = $valor2;

                // Normalização e Limpeza
                $codigoNorm = ltrim($codigo, '0');
                
                // Remover -PDD do título conforme solicitado
                $titulo = str_replace('-PDD', '', $tituloRaw);
                $tituloNorm = $titulo; // Agora são iguais
                $cpfNorm = Normalizer::cpfCnpj($cpf);
                
                // Check existence
                $stmtCheck->execute([$codigoNorm, $tituloNorm]);
                if (!$stmtCheck->fetch()) {
                    $stmtInsert->execute([
                        $batchId, $titulo, $codigo, $cliente, $cpf, $situacao, $vencimento, $valorTitulo,
                        $codigoNorm, $tituloNorm, $cpfNorm
                    ]);
                }
            } else {
                // Log failed lines that look like they might have data (contain digits and PDD)
                if (strpos($line, 'PDD') !== false || preg_match('/\d{3}\.\d{3}\.\d{3}/', $line)) {
                    $log .= "FAILED MATCH: $line\n";
                }
            }
        }
        
        $log .= "Total Matches: $matchesCount\n";
        file_put_contents('debug_pdd_pagos.log', $log, FILE_APPEND);
    }
}
