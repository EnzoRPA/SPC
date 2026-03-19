<?php

namespace App;

use PDO;

class Comparator {
    private $db;
    private $driver;

    public function __construct($db) {
        if (!$db) {
            throw new \Exception('Database connection failed. Cannot initialize Comparator.');
        }
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
        $dateFilter = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $dateFilter = " AND s.vencimento BETWEEN :start_date AND :end_date ";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        // 1. Missing from Open/PDD Logic
        // Remove if:
        // (Not in Open AND Not in PDD)
        // OR
        // (Not in Open AND In PDD BUT Paid)
        
        $sql = "
            SELECT s.*, 'spc_inclusos' as source_table,
                   CASE 
                       WHEN EXISTS (SELECT 1 FROM parcelas_em_aberto pa WHERE pa.contrato_norm = s.contrato_norm AND pa.vencimento = s.vencimento AND s.contrato_norm != '') THEN 'CPF Divergente'
                       WHEN pp.id IS NOT NULL AND pg_check.id IS NOT NULL THEN 'PDD PAGO (Titulo)'
                       ELSE 'Sem Parcela / Falso PDD' 
                   END as motivo,
                   s.debito as valor
            FROM spc_inclusos s
            LEFT JOIN parcelas_em_aberto p 
                ON s.cpf_cnpj_norm = p.cpf_cnpj_norm 
                AND s.contrato_norm = p.contrato_norm
                AND s.vencimento = p.vencimento
            LEFT JOIN pdd_perdas pp
                ON s.contrato_norm = pp.codigo_contrato_norm
            LEFT JOIN pdd_pagos pg_check
                ON (
                    (pp.codigo_venda = pg_check.titulo_norm AND pp.codigo_venda != '' AND pp.codigo_venda = s.venda)
                    OR
                    (pp.codigo_contrato_norm = pg_check.codigo_norm AND pp.codigo_contrato_norm != '')
                ) AND pp.id IS NOT NULL
            LEFT JOIN spc_ignorados i
                ON s.contrato_norm = i.contrato_norm
                AND s.cpf_cnpj_norm = i.cpf_cnpj_norm
                AND (i.vencimento IS NULL OR s.vencimento = i.vencimento)
            WHERE p.id IS NULL -- Missing from Active Debts
            AND (
                pp.id IS NULL -- Also Missing from PDD Perdas
                OR
                pg_check.id IS NOT NULL -- OR it IS in PDD, but it is PAID
            )
            AND ( i.id IS NULL OR pg_check.id IS NOT NULL )
            -- Guard: parcelas vencidas há mais de 6 meses só saem do SPC se confirmadas como pagas (pdd_pagos)
            AND (
                s.vencimento > {$this->getDateSubSql('6 MONTH')}
                OR pg_check.id IS NOT NULL
            )
            $dateFilter
            
            UNION
            
            -- Keep existing explicit PDD PAGO check (legacy match by contract/title directly on spc_inclusos)
            SELECT s.*, 'spc_inclusos' as source_table, 'PDD PAGO' as motivo, s.debito as valor
            FROM spc_inclusos s
            JOIN pdd_pagos pg
                ON (
                    (s.contrato_norm = pg.codigo_norm AND s.contrato_norm != '')
                    OR (s.contrato_norm = pg.titulo_norm AND s.contrato_norm != '')
                    OR (s.venda != '' AND s.venda = pg.titulo_norm)
                )
            -- HERE: If it's in pdd_pagos, we NEVER ignore it. It must be excluded.
            WHERE 1=1
            $dateFilter
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterParaInclusao($startDate = null, $endDate = null) {
        $dateFilter = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $dateFilter = " AND p.vencimento BETWEEN :start_date AND :end_date ";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        $vencAdjusted = $this->getVencimentoAdjustedSql('p.vencimento');
        $dateSub6Month = $this->getDateSubSql('6 MONTH'); // Adjusted logic: PDD usually old
        $dateSub5Year = $this->getDateSubSql('5 YEAR');
        
        // UNION Query requires matching columns. We explicitly list them.
        // Columns needed: id, contrato, tp_contrato, contratante, contratacao, cpf_cnpj, status, 
        // venda, parcela, debito, emissao, vencimento, dias_atraso, rua, numero, bairro, cep, cidade, estado, motivo
        
        $sql = "
            SELECT p.id, 'parcelas_em_aberto' as source_table, p.batch_id, p.contrato, p.tp_contrato, p.contratante, p.contratacao, p.cpf_cnpj, p.status, 
                   p.venda, p.parcela, p.debito, p.emissao, p.vencimento, p.dias_atraso, 
                   p.rua, p.numero, p.bairro, p.cep, p.cidade, p.estado,
                   p.contrato_norm, p.cpf_cnpj_norm,
                   CASE 
                       WHEN pp.id IS NOT NULL AND pp.data_vencimento <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) THEN 'PDD PERDAS' 
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
            LEFT JOIN spc_ignorados i 
                ON p.contrato_norm = i.contrato_norm
                AND p.cpf_cnpj_norm = i.cpf_cnpj_norm
                AND (i.vencimento IS NULL OR p.vencimento = i.vencimento)
            WHERE s.id IS NULL
            AND pg.id IS NULL
            AND i.id IS NULL -- Filter out ignored records
            AND $vencAdjusted >= $dateSub5Year
            AND (p.contratante NOT LIKE 'Unimed Maranhão Do Sul%' OR p.contratante IS NULL)
            -- SafeGuard: Demitidos/Aposentados MUST be individuals (CPF), not Companies (CNPJ)
            AND NOT (
                (p.tp_contrato LIKE '%Demitidos%' OR p.tp_contrato LIKE '%Aposentados%')
                AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(p.cpf_cnpj, '.', ''), '-', ''), '/', '')) > 11
            )
            $dateFilter

            UNION

            SELECT 
                pp.id,
                'pdd_perdas' as source_table,
                pp.batch_id,
                pp.codigo_contrato as contrato,
                'PDD' as tp_contrato,
                COALESCE(pp.nome, h.contratante, 'Cliente PDD') as contratante,
                COALESCE(pp.data_contratacao, h.data_inclusao_spc) as contratacao, 
                COALESCE(pp.cpf_cnpj, h.cpf_cnpj, 'CPF NAO ENCONTRADO') as cpf_cnpj,
                'PDD PERDAS' as status,
                pp.codigo_venda as venda,
                '1' as parcela,
                pp.valor as debito,
                pp.data_vencimento as emissao,
                pp.data_vencimento as vencimento,
                DATEDIFF(CURDATE(), pp.data_vencimento) as dias_atraso,
                pp.rua, pp.numero, pp.bairro, pp.cep, pp.cidade, pp.estado,
                pp.codigo_contrato_norm, pp.cpf_cnpj,
                CASE 
                    WHEN pp.data_vencimento <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) THEN 'PDD PERDAS (Importado)'
                    ELSE 'EM ABERTO' -- User requested classification as normal debt if < 6 months
                END as motivo
            FROM pdd_perdas pp
            LEFT JOIN spc_inclusos s ON pp.codigo_contrato_norm = s.contrato_norm
            LEFT JOIN pdd_pagos pg ON pp.codigo_venda = pg.titulo_norm
            LEFT JOIN (
                SELECT contrato, MAX(contratante) as contratante, MAX(data_inclusao_spc) as data_inclusao_spc, MAX(cpf_cnpj) as cpf_cnpj 
                FROM spc_historico_removidos 
                GROUP BY contrato
            ) h ON pp.codigo_contrato_norm = h.contrato
            WHERE s.id IS NULL -- Not in SPC
            AND pg.id IS NULL -- Not Paid
            AND pp.data_vencimento >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR) -- Safety check for prescribed
            -- Ensure we don't pick up items that are already in Parcelas (Duplicate protection on UNION)
            AND NOT EXISTS (
                SELECT 1 FROM parcelas_em_aberto pa WHERE pa.contrato_norm = pp.codigo_contrato_norm
            )
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

    public function enriquecerNomesPdd() {
        // Encontra todos os PDD Perdas sem nome (ou Cliente PDD) mas que têm algum CPF válido
        $sql = "
            SELECT DISTINCT cpf_cnpj, codigo_contrato_norm 
            FROM pdd_perdas 
            WHERE (nome IS NULL OR nome = '' OR nome = 'Cliente PDD') 
            AND cpf_cnpj IS NOT NULL AND cpf_cnpj != '' AND cpf_cnpj != 'CPF NAO ENCONTRADO'
        ";
        $stmt = $this->db->query($sql);
        $missing = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updateCount = 0;
        foreach ($missing as $m) {
            if (!$m['cpf_cnpj']) continue;
            $cpf = preg_replace('/\D/', '', $m['cpf_cnpj']);
            if (empty($cpf)) continue;
            
            $name = null;
            
            // Busca em parcelas_em_aberto
            $stmtFind = $this->db->prepare("SELECT contratante FROM parcelas_em_aberto WHERE cpf_cnpj_norm = ? AND contratante IS NOT NULL AND contratante != '' LIMIT 1");
            $stmtFind->execute([$cpf]);
            if ($row = $stmtFind->fetch()) {
                $name = $row['contratante'];
            } else {
                // Busca em spc_inclusos
                $stmtFind = $this->db->prepare("SELECT contratante FROM spc_inclusos WHERE cpf_cnpj_norm = ? AND contratante IS NOT NULL AND contratante != '' LIMIT 1");
                $stmtFind->execute([$cpf]);
                if ($row = $stmtFind->fetch()) {
                    $name = $row['contratante'];
                } else {
                    // Busca em spc_historico_removidos
                    $stmtFind = $this->db->prepare("SELECT contratante FROM spc_historico_removidos WHERE cpf_cnpj = ? AND contratante IS NOT NULL AND contratante != '' LIMIT 1");
                    $stmtFind->execute([$cpf]);
                    if ($row = $stmtFind->fetch()) {
                        $name = $row['contratante'];
                    } else {
                        // Busca em outros pdd_perdas que tenham o nome
                        $stmtFind = $this->db->prepare("SELECT nome FROM pdd_perdas WHERE cpf_cnpj = ? AND nome IS NOT NULL AND nome != '' AND nome != 'Cliente PDD' LIMIT 1");
                        $stmtFind->execute([$m['cpf_cnpj']]);
                        if ($row = $stmtFind->fetch()) {
                            $name = $row['nome'];
                        }
                    }
                }
            }
            
            if ($name) {
                $stmtUpdate = $this->db->prepare("UPDATE pdd_perdas SET nome = ? WHERE cpf_cnpj = ? AND (nome IS NULL OR nome = '' OR nome = 'Cliente PDD')");
                $stmtUpdate->execute([$name, $m['cpf_cnpj']]);
                $updateCount += $stmtUpdate->rowCount();
            }
        }
        return $updateCount;
    }

    public function enriquecerCpfsPdd() {
        // Encontra todos os PDD Perdas com CPF vazio ou 'CPF NAO ENCONTRADO' mas que têm contrato válido
        $sql = "
            SELECT DISTINCT codigo_contrato_norm 
            FROM pdd_perdas 
            WHERE (cpf_cnpj IS NULL OR cpf_cnpj = '' OR cpf_cnpj = 'CPF NAO ENCONTRADO' OR cpf_cnpj = '0') 
            AND codigo_contrato_norm IS NOT NULL AND codigo_contrato_norm != ''
        ";
        $stmt = $this->db->query($sql);
        $missing = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updateCount = 0;
        foreach ($missing as $m) {
            $contrato = $m['codigo_contrato_norm'];
            $cpf = null;
            
            // Busca em parcelas_em_aberto
            $stmtFind = $this->db->prepare("SELECT cpf_cnpj FROM parcelas_em_aberto WHERE contrato_norm = ? AND cpf_cnpj IS NOT NULL AND cpf_cnpj != '' AND cpf_cnpj != 'CPF NAO ENCONTRADO' LIMIT 1");
            $stmtFind->execute([$contrato]);
            if ($row = $stmtFind->fetch()) {
                $cpf = $row['cpf_cnpj'];
            } else {
                // Busca em spc_inclusos
                $stmtFind = $this->db->prepare("SELECT cpf_cnpj FROM spc_inclusos WHERE contrato_norm = ? AND cpf_cnpj IS NOT NULL AND cpf_cnpj != '' AND cpf_cnpj != 'CPF NAO ENCONTRADO' LIMIT 1");
                $stmtFind->execute([$contrato]);
                if ($row = $stmtFind->fetch()) {
                    $cpf = $row['cpf_cnpj'];
                } else {
                    // Busca em spc_historico_removidos
                    $stmtFind = $this->db->prepare("SELECT cpf_cnpj FROM spc_historico_removidos WHERE contrato = ? AND cpf_cnpj IS NOT NULL AND cpf_cnpj != '' AND cpf_cnpj != 'CPF NAO ENCONTRADO' LIMIT 1");
                    $stmtFind->execute([$contrato]);
                    if ($row = $stmtFind->fetch()) {
                        $cpf = $row['cpf_cnpj'];
                    } else {
                        // Busca em outros pdd_perdas que tenham o cpf
                        $stmtFind = $this->db->prepare("SELECT cpf_cnpj FROM pdd_perdas WHERE codigo_contrato_norm = ? AND cpf_cnpj IS NOT NULL AND cpf_cnpj != '' AND cpf_cnpj != 'CPF NAO ENCONTRADO' LIMIT 1");
                        $stmtFind->execute([$contrato]);
                        if ($row = $stmtFind->fetch()) {
                            $cpf = $row['cpf_cnpj'];
                        }
                    }
                }
            }
            
            if ($cpf) {
                // Update just the cpf_cnpj (no norm column usually in pdd_perdas)
                $stmtUpdate = $this->db->prepare("UPDATE pdd_perdas SET cpf_cnpj = ? WHERE codigo_contrato_norm = ? AND (cpf_cnpj IS NULL OR cpf_cnpj = '' OR cpf_cnpj = 'CPF NAO ENCONTRADO' OR cpf_cnpj = '0')");
                $stmtUpdate->execute([$cpf, $contrato]);
                $updateCount += $stmtUpdate->rowCount();
            }
        }
        return $updateCount;
    }
}
