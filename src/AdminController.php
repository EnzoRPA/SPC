<?php

namespace App;

use PDO;

class AdminController {
    private $db;
    private $driver;

    public function __construct($db) {
        $this->db = $db;
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private function getTableColumns($table) {
        if ($this->driver === 'pgsql') {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = ?";
            // Postgres stores table names in lowercase usually, but let's be safe
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$table]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // MySQL
            $stmt = $this->db->prepare("DESCRIBE $table");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    private function quoteIdentifier($id) {
        if ($this->driver === 'pgsql') {
            return '"' . $id . '"';
        }
        return "`" . $id . "`";
    }

    public function listTable($table, $page = 1, $perPage = 50, $search = '', $filters = []) {
        $offset = ($page - 1) * $perPage;
        $allowedTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos', 'import_batches', 'spc_historico_removidos'];
        
        if (!in_array($table, $allowedTables)) {
            throw new \Exception("Tabela inválida");
        }

        $conditions = [];
        $params = [];

        // Global Search
        if ($search) {
            $columns = $this->getTableColumns($table);

            $searchConditions = [];
            foreach ($columns as $col) {
                // Postgres requires casting to text for LIKE if column is not text
                if ($this->driver === 'pgsql') {
                    $searchConditions[] = "$col::text LIKE ?";
                } else {
                    $searchConditions[] = "$col LIKE ?";
                }
                $params[] = "%$search%";
            }
            if (!empty($searchConditions)) {
                $conditions[] = "(" . implode(' OR ', $searchConditions) . ")";
            }
        }

        // Column Filters
        if (!empty($filters)) {
            foreach ($filters as $col => $values) {
                // Validate column name (basic check)
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) continue;
                
                if (is_array($values) && !empty($values)) {
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $conditions[] = "$col IN ($placeholders)";
                    foreach ($values as $val) {
                        $params[] = $val;
                    }
                }
            }
        }

        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(' AND ', $conditions);
        }

        // Count total
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM $table $where");
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        // Fetch data
        $stmt = $this->db->prepare("SELECT * FROM $table $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page
        ];
    }

    public function getColumnValues($table, $column) {
        $allowedTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos', 'import_batches', 'spc_historico_removidos'];
        if (!in_array($table, $allowedTables)) {
            throw new \Exception("Tabela inválida");
        }

        // Validate column
        $columns = $this->getTableColumns($table);
        if (!in_array($column, $columns)) {
            throw new \Exception("Coluna inválida");
        }

        // Get distinct values and counts
        $colQuoted = $this->quoteIdentifier($column);
        $sql = "SELECT $colQuoted as value, COUNT(*) as count FROM $table GROUP BY $colQuoted ORDER BY $colQuoted ASC LIMIT 1000";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($table) {
        return $this->getExportData($table);
    }

    public function getExportData($table, $search = '', $filters = []) {
        $allowedTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos', 'import_batches', 'spc_historico_removidos'];
        
        if (!in_array($table, $allowedTables)) {
            throw new \Exception("Tabela inválida");
        }

        $conditions = [];
        $params = [];

        // Reuse logic from listTable (DRY principle would suggest refactoring, but for now we duplicate the query build)
        
        // Global Search
        if ($search) {
            $columns = $this->getTableColumns($table);

            $searchConditions = [];
            foreach ($columns as $col) {
                if ($this->driver === 'pgsql') {
                    $colQuoted = $this->quoteIdentifier($col);
                    $searchConditions[] = "$colQuoted::text LIKE ?";
                } else {
                    $searchConditions[] = $this->quoteIdentifier($col) . " LIKE ?";
                }
                $params[] = "%$search%";
            }
            if (!empty($searchConditions)) {
                $conditions[] = "(" . implode(' OR ', $searchConditions) . ")";
            }
        }

        // Column Filters
        if (!empty($filters)) {
            foreach ($filters as $col => $values) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) continue;
                
                if (is_array($values) && !empty($values)) {
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $conditions[] = "$col IN ($placeholders)";
                    foreach ($values as $val) {
                        $params[] = $val;
                    }
                }
            }
        }

        $where = "";
        if (!empty($conditions)) {
            $where = "WHERE " . implode(' AND ', $conditions);
        }

        // Fetch all data ordered by ID desc
        $stmt = $this->db->prepare("SELECT * FROM $table $where ORDER BY id DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($table, $id) {
        $allowedTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos', 'import_batches', 'spc_historico_removidos'];
        if (!in_array($table, $allowedTables)) throw new \Exception("Tabela inválida");

        // Special handling for import_batches to ensure cascade delete
        if ($table === 'import_batches') {
            $this->db->beginTransaction();
            try {
                // Delete from child tables
                $childTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos', 'spc_excluidos'];
                foreach ($childTables as $childTable) {
                    $stmt = $this->db->prepare("DELETE FROM $childTable WHERE batch_id = ?");
                    $stmt->execute([$id]);
                }

                // Delete the batch
                $stmt = $this->db->prepare("DELETE FROM import_batches WHERE id = ?");
                $stmt->execute([$id]);
                
                $this->db->commit();
                return true;
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        }

        // Archive before delete if it's spc_inclusos
        if ($table === 'spc_inclusos') {
            $this->archiveRecord($id);
        }

        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function archiveRecord($id) {
        try {
            // Fetch original record
            $stmt = $this->db->prepare("SELECT * FROM spc_inclusos WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                $stmtInsert = $this->db->prepare("INSERT INTO spc_historico_removidos (
                    original_id, contrato, tp_contrato, contratante, cpf_cnpj, valor, vencimento, data_inclusao_spc, motivo_remocao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Handle potential invalid dates
                $vencimento = ($record['vencimento'] === '0000-00-00' || empty($record['vencimento'])) ? null : $record['vencimento'];
                $dataInclusao = ($record['data_inclusao'] === '0000-00-00' || empty($record['data_inclusao'])) ? null : $record['data_inclusao'];
                
                $stmtInsert->execute([
                    $record['id'],
                    $record['contrato'],
                    $record['tp_contrato'],
                    $record['contratante'],
                    $record['cpf_cnpj'],
                    $record['debito'],
                    $vencimento,
                    $dataInclusao,
                    'Removido manualmente pelo Admin'
                ]);
            }
        } catch (\Exception $e) {
            // Log error to absolute path to be sure
            $logFile = __DIR__ . '/../debug_archive_error.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error archiving ID $id: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Update genérico (simplificado)
    public function update($table, $id, $data) {
        $allowedTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos'];
        if (!in_array($table, $allowedTables)) throw new \Exception("Tabela inválida");
        
        // Filtrar campos permitidos seria ideal, aqui vamos confiar no input por enquanto (admin only)
        // Remover campos que não devem ser alterados (id, batch_id)
        unset($data['id']);
        unset($data['batch_id']);
        
        $fields = array_keys($data);
        $set = implode('=?, ', $fields) . '=?';
        $values = array_values($data);
        $values[] = $id;
        
        $sql = "UPDATE $table SET $set WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function updateCell($table, $id, $column, $value) {
        $allowedTables = ['spc_inclusos', 'parcelas_em_aberto', 'pdd_perdas', 'pdd_pagos', 'import_batches'];
        if (!in_array($table, $allowedTables)) {
            throw new \Exception("Tabela inválida");
        }

        // Validate column name to prevent SQL injection
        // Get valid columns for the table
        $columns = $this->getTableColumns($table);

        if (!in_array($column, $columns)) {
            throw new \Exception("Coluna inválida: $column");
        }

        // Prevent updating ID
        if ($column === 'id') {
            throw new \Exception("Não é permitido alterar o ID");
        }

        $colQuoted = $this->quoteIdentifier($column);
        $sql = "UPDATE $table SET $colQuoted = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$value, $id]);
    }
}
