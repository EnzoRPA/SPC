<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class ParcelasImporter implements ImportStrategy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        // Full Refresh: Limpar dados anteriores
        $this->db->exec("TRUNCATE TABLE parcelas_em_aberto");

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        $header = array_shift($rows);
        
        // Mapeamento dinâmico de colunas
        $colMap = [];
        foreach ($header as $index => $colName) {
            if (!empty($colName)) {
                $colMap[strtoupper(trim($colName))] = $index;
            }
        }

        // Função auxiliar para pegar valor
        $getValue = function($row, $colName, $defaultIndex) use ($colMap) {
            $key = strtoupper($colName);
            if (isset($colMap[$key])) {
                return $row[$colMap[$key]] ?? null;
            }
            // Fallback para índice fixo APENAS se o mapeamento falhar completamente (ex: arquivo sem cabeçalho)
            // Mas se detectamos colunas extras (ID/BATCH_ID), o índice fixo estaria errado.
            // Vamos assumir que se achou 'CONTRATO', usa o mapa. Se não, tenta o defaultIndex.
            return $row[$defaultIndex] ?? null;
        };
        
        $stmt = $this->db->prepare("
            INSERT INTO parcelas_em_aberto (
                batch_id, contrato, tp_contrato, contratante, contratacao, cpf_cnpj, status, venda, parcela, debito, emissao, vencimento, dias_atraso, rua, numero, bairro, cep, cidade, estado,
                cpf_cnpj_norm, contrato_norm
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $index => $row) {
            // Check if row is visible (respect Excel filters)
            // $rows is 0-indexed. We shifted one off.
            // So $index 0 in $rows corresponds to Excel Row 2.
            $excelRowIndex = $index + 2;
            if (!$sheet->getRowDimension($excelRowIndex)->getVisible()) {
                continue;
            }

            // Pula linhas vazias
            if (empty($row[0]) && empty($row[1])) continue;

            $contrato = $getValue($row, 'CONTRATO', 0);

            // DEBUG: Log everything for first 5 rows and specific contract
            if ($index < 5 || strpos((string)$contrato, '32801') !== false) {
                 $logData = "Row $index:\n";
                 $logData .= "  Contrato (Col 0/Mapped): '$contrato'\n";
                 $logData .= "  Raw Row: " . json_encode($row) . "\n";
                 file_put_contents('debug_importer_general_log.txt', $logData, FILE_APPEND);
            }
            $tpContrato = $getValue($row, 'TP_CONTRATO', 1);
            $contratante = $getValue($row, 'CONTRATANTE', 2);
            $contratacao = Normalizer::data($getValue($row, 'CONTRATACAO', 3));
            $cpf = $getValue($row, 'CPF_CNPJ', 4);
            $status = $getValue($row, 'STATUS', 5);
            $venda = $getValue($row, 'VENDA', 6);
            $parcela = $getValue($row, 'PARCELA', 7);
            $debito = Normalizer::valor($getValue($row, 'DEBITO', 8) ?? 0);
            $emissao = Normalizer::data($getValue($row, 'EMISSAO', 9));
            $vencimento = Normalizer::data($getValue($row, 'VENCIMENTO', 10));
            $diasAtraso = (int)($getValue($row, 'DIAS_ATRASO', 11) ?? 0);

            // Fix: Calculate delay from due date if provided delay is suspicious (< 60)
            // This handles cases where Excel formula is broken or column is empty
            if ($diasAtraso < 60 && !empty($vencimento)) {
                try {
                    $vencDate = new \DateTime($vencimento);
                    $today = new \DateTime();
                    // Reset times to compare dates only
                    $vencDate->setTime(0, 0, 0);
                    $today->setTime(0, 0, 0);
                    
                    if ($vencDate < $today) {
                        $diff = $today->diff($vencDate);
                        $calculatedDelay = $diff->days; // absolute days difference
                        
                        if ($calculatedDelay > $diasAtraso) {
                            $diasAtraso = (int)$calculatedDelay;
                        }
                    }
                } catch (\Exception $e) {
                    // Invalid date, ignore calculation
                }
            }

            // Filter: Only import if delay is 60 days or more
            if ($diasAtraso < 60) {
                continue;
            }
            $rua = $getValue($row, 'RUA', 12);
            $numero = $getValue($row, 'NUMERO', 13);
            $bairro = $getValue($row, 'BAIRRO', 14);
            $cep = $getValue($row, 'CEP', 15);
            $cidade = $getValue($row, 'CIDADE', 16);
            $estado = $getValue($row, 'ESTADO', 17);
            
            $cpfNorm = Normalizer::cpfCnpj($cpf);
            $contratoNorm = Normalizer::contrato($contrato);

            if ($cpfNorm) {
                $stmt->execute([
                    $batchId, $contrato, $tpContrato, $contratante, $contratacao, $cpf, $status, $venda, $parcela, $debito, $emissao, $vencimento, $diasAtraso, $rua, $numero, $bairro, $cep, $cidade, $estado,
                    $cpfNorm, $contratoNorm
                ]);
            }
        }
    }
}
