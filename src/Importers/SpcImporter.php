<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class SpcImporter implements ImportStrategy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        // Full Refresh: Limpar dados anteriores (ou marcar como deletados, mas o requisito diz substituir)
        // Para manter histórico, poderíamos não deletar, mas o user pediu "substituição".
        // Vamos deletar tudo da tabela spc_inclusos para simplificar, ou deletar por batch se quisermos manter histórico de batches (mas a tabela spc_inclusos é a "current state").
        // O user disse: "registros não presentes no arquivo mais recente devem ser apagados".
        // A melhor forma é: Truncate table antes de importar? Ou importar novo batch e deletar antigos?
        // Vamos fazer: Delete all from spc_inclusos.
        
        // Full Refresh: Limpar dados anteriores (ou marcar como deletados, mas o requisito diz substituir)
        // UPDATE: User requested APPEND mode. Do not truncate.
        // $this->db->exec("TRUNCATE TABLE spc_inclusos");

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        // Use raw values (false for formatData) to get Excel serial dates instead of formatted strings
        $rows = $sheet->toArray(null, true, false, false);
        
        // Remove header
        $header = array_shift($rows);
        // Adjust index offset since we shifted the header
        // Original Row 1 was header. $rows[0] is now Original Row 2.
        
        $stmt = $this->db->prepare("
            INSERT INTO spc_inclusos (
                batch_id, contrato, tp_contrato, contratante, contratacao, cpf_cnpj, status, 
                venda, parcela, debito, emissao, vencimento, dias_atraso, 
                rua, numero, bairro, cep, cidade, estado, 
                nascimento, linha, status_inclusao, data_inclusao, hora_inclusao,
                cpf_cnpj_norm, contrato_norm
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $index => $row) {
            // Check if row is visible (respect Excel filters)
            // $rows is 0-indexed. We shifted one off.
            // So $index 0 in $rows corresponds to Excel Row 2.
            $excelRowIndex = $index + 2;
            if (!$sheet->getRowDimension($excelRowIndex)->getVisible()) {
                continue;
            }

            $contrato = $row[0] ?? null;
            $tpContrato = $row[1] ?? null;
            $contratante = $row[2] ?? null;
            $contratacao = Normalizer::data($row[3] ?? null);
            $cpf = $row[4] ?? null;
            $status = $row[5] ?? null;
            $venda = $row[6] ?? null;
            $parcela = $row[7] ?? null;
            $debito = Normalizer::valor($row[8] ?? 0);
            $emissao = Normalizer::data($row[9] ?? null);
            $vencimento = Normalizer::data($row[10] ?? null);
            $diasAtraso = (int)($row[11] ?? 0);
            $rua = $row[12] ?? null;
            $numero = $row[13] ?? null;
            $bairro = $row[14] ?? null;
            $cep = $row[15] ?? null;
            $cidade = $row[16] ?? null;
            $estado = $row[17] ?? null;
            $nascimento = Normalizer::data($row[18] ?? null);
            $linha = $row[19] ?? null;
            $statusInclusao = $row[20] ?? null;
            $dataInclusao = Normalizer::data($row[21] ?? null);
            $horaInclusao = $row[22] ?? null; // Pode precisar de tratamento se for Excel time
            
            $cpfNorm = Normalizer::cpfCnpj($cpf);
            $contratoNorm = Normalizer::contrato($contrato);

            if ($cpfNorm) {
                $stmt->execute([
                    $batchId, $contrato, $tpContrato, $contratante, $contratacao, $cpf, $status,
                    $venda, $parcela, $debito, $emissao, $vencimento, $diasAtraso,
                    $rua, $numero, $bairro, $cep, $cidade, $estado,
                    $nascimento, $linha, $statusInclusao, $dataInclusao, $horaInclusao,
                    $cpfNorm, $contratoNorm
                ]);
            }
        }
    }
}
