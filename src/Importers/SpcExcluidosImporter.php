<?php

namespace App\Importers;

use App\Helpers\Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;

class SpcExcluidosImporter {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function import($filePath, $batchId) {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header
        $header = array_shift($rows);
        
        // Map columns (simple search for keywords)
        $map = $this->mapColumns($header);

        if (!isset($map['cpf']) && !isset($map['contrato'])) {
            throw new \Exception("Colunas 'PF / CNPJ' ou 'CONTRATO' não encontradas no arquivo de excluídos.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO spc_excluidos (
                batch_id, cpf_cnpj, contrato, vencimento, data_exclusao,
                cpf_cnpj_norm, contrato_norm
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?
            )
        ");

        foreach ($rows as $index => $row) {
            // Check if row is visible (respect Excel filters)
            // $rows is 0-indexed. We shifted one off.
            // So $index 0 in $rows corresponds to Excel Row 2.
            $excelRowIndex = $index + 2;
            if (!$worksheet->getRowDimension($excelRowIndex)->getVisible()) {
                continue;
            }

            $cpf = $row[$map['cpf']] ?? null;
            $contrato = $row[$map['contrato']] ?? null;
            $vencimentoRaw = isset($map['vencimento']) ? ($row[$map['vencimento']] ?? null) : null;
            
            // Se não tiver CPF nem Contrato, pula
            if (!$cpf && !$contrato) continue;

            $cpfNorm = Normalizer::cpfCnpj($cpf);
            $contratoNorm = Normalizer::contrato($contrato);
            $vencimento = Normalizer::data($vencimentoRaw);
            
            // Data de exclusão: hoje (assumindo que o arquivo reflete o estado atual)
            $dataExclusao = date('Y-m-d');

            // Check for duplicates in spc_excluidos
            $stmtCheck = $this->db->prepare("SELECT id FROM spc_excluidos WHERE cpf_cnpj_norm = ? AND contrato_norm = ?");
            $stmtCheck->execute([$cpfNorm, $contratoNorm]);
            
            if (!$stmtCheck->fetch()) {
                $stmt->execute([
                    $batchId, $cpf, $contrato, $vencimento, $dataExclusao,
                    $cpfNorm, $contratoNorm
                ]);
            }

            // --- NEW LOGIC: Archive and Delete from spc_inclusos ---
            // Find matching record in spc_inclusos
            // Matching criteria: CPF/CNPJ AND Contrato (normalized)
            // Optional: Check Vencimento if provided? For now, let's stick to strict CPF+Contrato match.
            
            $sqlFind = "SELECT * FROM spc_inclusos WHERE cpf_cnpj_norm = ? AND contrato_norm = ?";
            $stmtFind = $this->db->prepare($sqlFind);
            $stmtFind->execute([$cpfNorm, $contratoNorm]);
            $record = $stmtFind->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                // Archive
                $sqlArchive = "INSERT INTO spc_historico_removidos (
                    original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtArchive = $this->db->prepare($sqlArchive);
                
                // Handle dates
                $vencimentoArch = ($record['vencimento'] === '0000-00-00' || empty($record['vencimento'])) ? null : $record['vencimento'];
                $dataInclusaoArch = ($record['data_inclusao'] === '0000-00-00' || empty($record['data_inclusao'])) ? null : $record['data_inclusao'];

                $stmtArchive->execute([
                    $record['id'],
                    $record['contrato'],
                    $record['tp_contrato'],
                    $record['contratante'],
                    $record['cpf_cnpj'],
                    $record['debito'],
                    $vencimentoArch,
                    $dataInclusaoArch,
                    'Importação de Arquivo de Excluídos'
                ]);

                // Delete
                $sqlDelete = "DELETE FROM spc_inclusos WHERE id = ?";
                $stmtDelete = $this->db->prepare($sqlDelete);
                $stmtDelete->execute([$record['id']]);
            }
        }
    }

    private function mapColumns($header) {
        $map = [];
        foreach ($header as $index => $colName) {
            $colName = mb_strtolower(trim($colName), 'UTF-8');
            
            // Mapeamento baseado na imagem do usuário
            if (strpos($colName, 'pf / cnpj') !== false || strpos($colName, 'cpf') !== false || strpos($colName, 'cnpj') !== false) {
                $map['cpf'] = $index;
            } elseif (strpos($colName, 'contrato') !== false) {
                $map['contrato'] = $index;
            } elseif (strpos($colName, 'vencimento') !== false) {
                $map['vencimento'] = $index;
            }
        }
        return $map;
    }
}
