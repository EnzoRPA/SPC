<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class PddPerdasImporter implements ImportStrategy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        // Append Only: Não limpa a tabela.
        // O user disse: "considerar apenas colunas corretas para comparação: Data de Vencimento, Código do Contrato"
        // Chave de unicidade: (codigo_contrato_norm, data_vencimento).
        // Vamos usar INSERT IGNORE ou verificar antes. Como MySQL não tem INSERT IGNORE padrão em todos os modos, vamos usar ON DUPLICATE KEY UPDATE id=id (no-op) se tiver chave unica, mas não definimos chave unica no schema ainda.
        // Vamos fazer um select check simples para evitar duplicatas exatas deste batch, mas o user disse "podem existir repetidas".
        // "Em novo upload, adicionar apenas os que ainda não existem".
        
        // Vamos ler em chunks se for muito grande, mas aqui faremos simples.
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Use raw values (false for formatData) to get Excel serial dates instead of formatted strings
        // This solves issues where dates are formatted as MM/DD/YYYY in the cell but we expect DD/MM/YYYY strings.
        // Raw values return the Excel serial number (float/int) which Normalizer::data handles correctly.
        $rows = $sheet->toArray(null, true, false, false);
        
        // Header
        $header = array_shift($rows);
        
        // Precisamos identificar as colunas pelo nome, pois o user disse que "podem existir repetidas" e "considerar apenas colunas corretas".
        // Vamos procurar os índices de "Data de Vencimento" e "Código do Contrato".
        
        $idxVencimento = -1;
        $idxContrato = -1;
        $idxVenda = -1;
        
        foreach ($header as $i => $colName) {
            $colName = mb_strtoupper(trim($colName), 'UTF-8');
            if (strpos($colName, 'VENCIMENTO') !== false) $idxVencimento = $i;
            if (strpos($colName, 'CONTRATO') !== false) $idxContrato = $i;
            if (strpos($colName, 'VENDA') !== false) $idxVenda = $i;
        }
        
        if ($idxVencimento === -1 || $idxContrato === -1) {
            throw new \Exception("Colunas 'Data de Vencimento' ou 'Código do Contrato' não encontradas na planilha PDD Perdas.");
        }

        $stmtCheck = $this->db->prepare("SELECT id FROM pdd_perdas WHERE codigo_contrato_norm = ? AND data_vencimento = ? LIMIT 1");
        $stmtInsert = $this->db->prepare("
            INSERT INTO pdd_perdas (
                batch_id, codigo_venda, codigo_contrato, data_vencimento, codigo_contrato_norm
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($rows as $index => $row) {
            // Check if row is visible (respect Excel filters)
            // $rows is 0-indexed. We shifted one off.
            // So $index 0 in $rows corresponds to Excel Row 2.
            $excelRowIndex = $index + 2;
            if (!$sheet->getRowDimension($excelRowIndex)->getVisible()) {
                continue;
            }

            $contrato = $row[$idxContrato] ?? null;
            $venda = ($idxVenda !== -1) ? ($row[$idxVenda] ?? null) : null;
            
            // Limpeza de artefatos relatados (ex: _-123_-)
            if ($contrato) $contrato = trim($contrato, " _-");
            if ($venda) $venda = trim($venda, " _-");
            
            $vencimento = Normalizer::data($row[$idxVencimento] ?? null);
            $contratoNorm = Normalizer::contrato($contrato);
            
            if ($contratoNorm && $vencimento) {
                // Check existence
                $stmtCheck->execute([$contratoNorm, $vencimento]);
                if (!$stmtCheck->fetch()) {
                    $stmtInsert->execute([$batchId, $venda, $contrato, $vencimento, $contratoNorm]);
                }
            }
        }
    }
}
