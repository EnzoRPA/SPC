<?php

namespace App;

use PDO;

class Comparator {
    private $db;
    private $driver;

    public function __construct($db) {
        $this->db = $db;
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    
    // Helper to normalize date adjustment logic (fix Y2K/Century issues)
    private function getVencimentoAdjustedSql($column) {
        if ($this->driver === 'pgsql') {
            return "CASE WHEN EXTRACT(YEAR FROM $column) < 1900 THEN $column + INTERVAL '2000 years' ELSE $column END";
        }
        return "IF(YEAR($column) < 1900, DATE_ADD($column, INTERVAL 2000 YEAR), $column)";
    }
    
    // Helper for DATE_SUB(CURDATE(), ...)
    private function getDateSubSql($interval) {
        // Interval e.g. "6 MONTH", "5 YEAR"
        if ($this->driver === 'pgsql') {
            return "CURRENT_DATE - INTERVAL '$interval'";
        }
        return "DATE_SUB(CURDATE(), INTERVAL $interval)";
    }

    public function obterParaExclusao($startDate = null, $endDate = null) {
        // Regra:
        // 1. Em SPCINCLUSOS sem correspondente em Parcelas Em Aberto.
        // 2. OU constando em PDD Pagos (prioridade remover).
        
        $dateFilter = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $dateFilter = " AND s.vencimento BETWEEN :start_date AND :end_date ";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        $sql = "
            SELECT s.*, 
                   CASE 
                       WHEN EXISTS (SELECT 1 FROM parcelas_em_aberto pa WHERE pa.contrato_norm = s.contrato_norm AND s.contrato_norm != '') THEN 'CPF Divergente'
                       ELSE 'Sem Parcela' 
                   END as motivo,
                   s.debito as valor
            FROM spc_inclusos s
            LEFT JOIN parcelas_em_aberto p 
                ON s.cpf_cnpj_norm = p.cpf_cnpj_norm 
                AND s.contrato_norm = p.contrato_norm
            LEFT JOIN pdd_perdas pp
                ON s.contrato_norm = pp.codigo_contrato_norm
            LEFT JOIN spc_excluidos ex
                ON s.cpf_cnpj_norm = ex.cpf_cnpj_norm
                AND s.contrato_norm = ex.contrato_norm
                AND (ex.vencimento IS NULL OR s.vencimento = ex.vencimento)
            WHERE p.id IS NULL
            AND pp.id IS NULL
            AND ex.id IS NULL
            $dateFilter
            
            UNION
            
            SELECT s.*, 'PDD PAGO' as motivo, s.debito as valor
            FROM spc_inclusos s
            JOIN pdd_pagos pg
                ON (
                    (s.contrato_norm = pg.codigo_norm AND s.contrato_norm != '')
                    OR (s.contrato_norm = pg.titulo_norm AND s.contrato_norm != '')
                    OR (s.venda != '' AND s.venda = pg.titulo_norm)
                )
            LEFT JOIN spc_excluidos ex
                ON s.cpf_cnpj_norm = ex.cpf_cnpj_norm
                AND s.contrato_norm = ex.contrato_norm
                AND (ex.vencimento IS NULL OR s.vencimento = ex.vencimento)
            WHERE ex.id IS NULL
            $dateFilter
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterParaInclusao($startDate = null, $endDate = null) {
        // Regra:
        // 1. Em Parcelas Em Aberto sem correspondente em SPCINCLUSOS.
        // 2. E NÃO presente em PDD Pagos.
        
        $dateFilter = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $dateFilter = " AND p.vencimento BETWEEN :start_date AND :end_date ";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        $vencAdjusted = $this->getVencimentoAdjustedSql('p.vencimento');
        $dateSub6Month = $this->getDateSubSql('6 MONTH');
        $dateSub5Year = $this->getDateSubSql('5 YEAR');
        
        $sql = "
            SELECT DISTINCT p.*, 
                   CASE 
                       WHEN pp.id IS NOT NULL 
                            AND $vencAdjusted <= $dateSub6Month
                       THEN 'PDD PERDAS' 
                       ELSE 'EM ABERTO' 
                   END as motivo
            FROM parcelas_em_aberto p
            LEFT JOIN spc_inclusos s 
                ON p.cpf_cnpj_norm = s.cpf_cnpj_norm 
                AND p.contrato_norm = s.contrato_norm
            LEFT JOIN pdd_pagos pg
                ON (p.contrato_norm = pg.codigo_norm OR p.contrato_norm = pg.titulo_norm)
            LEFT JOIN pdd_perdas pp
                ON p.contrato_norm = pp.codigo_contrato_norm
            WHERE s.id IS NULL
            AND pg.id IS NULL
            AND $vencAdjusted >= $dateSub5Year
            AND (p.contratante NOT LIKE 'Unimed Maranhão Do Sul%' OR p.contratante IS NULL)
            $dateFilter
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function limparPrescritos() {
        // Archive before delete
        $vencAdjusted = $this->getVencimentoAdjustedSql('vencimento');
        $dateSub5Year = $this->getDateSubSql('5 YEAR');
        
        $sqlArchive = "INSERT INTO spc_historico_removidos (original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao)
                       SELECT id, contrato, tp_contrato, contratante, cpf_cnpj, debito, vencimento, data_inclusao, 'Prescrito (> 5 anos)'
                       FROM spc_inclusos 
                       WHERE $vencAdjusted < $dateSub5Year";
        $this->db->exec($sqlArchive);

        // Remove registros do SPC Inclusos com mais de 5 anos
        $sql = "DELETE FROM spc_inclusos 
                WHERE $vencAdjusted < $dateSub5Year";
        return $this->db->exec($sql);
    }

    public function limparVendasDuplicadas() {
        // Remove vendas duplicadas, mantendo apenas o registro com menor ID
        $tables = ['spc_inclusos', 'parcelas_em_aberto'];
        $totalRemoved = 0;
        
        $log = "=== Limpeza de Duplicatas " . date('Y-m-d H:i:s') . " ===\n";
        
        foreach ($tables as $table) {
            // 1. Remove duplicatas por venda
            
            // Archive first (only for spc_inclusos)
            if ($table === 'spc_inclusos') {
                $sqlArchive = "
                    INSERT INTO spc_historico_removidos (original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao)
                    SELECT t1.id, t1.contrato, t1.tp_contrato, t1.contratante, t1.cpf_cnpj, t1.debito, t1.vencimento, t1.data_inclusao, 'Duplicata de Venda (Limpeza Automática)'
                    FROM $table t1
                    INNER JOIN $table t2 
                    ON t1.venda = t2.venda 
                    WHERE t1.venda IS NOT NULL 
                    AND t1.venda != ''
                    AND t1.id > t2.id
                ";
                $this->db->exec($sqlArchive);
            }

            if ($this->driver === 'pgsql') {
                $sql = "
                    DELETE FROM $table t1
                    USING $table t2 
                    WHERE t1.venda = t2.venda 
                    AND t1.venda IS NOT NULL 
                    AND t1.venda != ''
                    AND t1.id > t2.id
                ";
            } else {
                $sql = "
                    DELETE t1 FROM $table t1
                    INNER JOIN $table t2 
                    WHERE t1.venda = t2.venda 
                    AND t1.venda IS NOT NULL 
                    AND t1.venda != ''
                    AND t1.id > t2.id
                ";
            }
            $removed1 = $this->db->exec($sql);
            $totalRemoved += $removed1;
            $log .= "Tabela $table - Removidos por venda: $removed1\n";
            
            // 2. Remove duplicatas por combinação de campos
            // CPF/CNPJ + Nome + Contrato + Vencimento + Valor
            $nameField = ($table === 'parcelas_em_aberto') ? 'contratante' : 'contratante';
            $valueField = ($table === 'parcelas_em_aberto') ? 'debito' : 'debito';
            
            // Archive first (only for spc_inclusos)
            if ($table === 'spc_inclusos') {
                $sqlArchive2 = "
                    INSERT INTO spc_historico_removidos (original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao)
                    SELECT t1.id, t1.contrato, t1.tp_contrato, t1.contratante, t1.cpf_cnpj, t1.debito, t1.vencimento, t1.data_inclusao, 'Duplicata de Campos (Limpeza Automática)'
                    FROM $table t1
                    INNER JOIN $table t2 
                    ON t1.cpf_cnpj_norm = t2.cpf_cnpj_norm
                    AND t1.$nameField = t2.$nameField
                    AND t1.contrato_norm = t2.contrato_norm
                    AND t1.vencimento = t2.vencimento
                    AND t1.$valueField = t2.$valueField
                    WHERE t1.id > t2.id
                ";
                $this->db->exec($sqlArchive2);
            }

            if ($this->driver === 'pgsql') {
                $sql = "
                    DELETE FROM $table t1
                    USING $table t2 
                    WHERE t1.cpf_cnpj_norm = t2.cpf_cnpj_norm
                    AND t1.$nameField = t2.$nameField
                    AND t1.contrato_norm = t2.contrato_norm
                    AND t1.vencimento = t2.vencimento
                    AND t1.$valueField = t2.$valueField
                    AND t1.id > t2.id
                ";
            } else {
                $sql = "
                    DELETE t1 FROM $table t1
                    INNER JOIN $table t2 
                    WHERE t1.cpf_cnpj_norm = t2.cpf_cnpj_norm
                    AND t1.$nameField = t2.$nameField
                    AND t1.contrato_norm = t2.contrato_norm
                    AND t1.vencimento = t2.vencimento
                    AND t1.$valueField = t2.$valueField
                    AND t1.id > t2.id
                ";
            }
            $removed2 = $this->db->exec($sql);
            $totalRemoved += $removed2;
            $log .= "Tabela $table - Removidos por campos: $removed2\n";
        }
        
        $log .= "Total removido: $totalRemoved\n\n";
        file_put_contents(__DIR__ . '/../debug_cleanup_log.txt', $log, FILE_APPEND);
        
        return $totalRemoved;
    }
}
