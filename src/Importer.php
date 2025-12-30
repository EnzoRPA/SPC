<?php

namespace App;

use App\Importers\SpcImporter;
use App\Importers\ParcelasImporter;
use App\Importers\PddPerdasImporter;
use App\Importers\PddPagosImporter;
use App\Importers\SpcExcluidosImporter;
use PDO;

class Importer {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function importarArquivo($arquivo, $tipo) {
        // Cria registro do lote
        $stmt = $this->db->prepare("INSERT INTO import_batches (filename, type) VALUES (?, ?)");
        $stmt->execute([basename($arquivo), $tipo]);
        $batchId = $this->db->lastInsertId();

        try {
            $strategy = null;

            switch ($tipo) {
                case 'spc':
                case 'spc_atualizacao': // New type, same strategy (Append)
                    $strategy = new SpcImporter($this->db);
                    break;
                case 'parcelas':
                    $strategy = new ParcelasImporter($this->db);
                    break;
                case 'pdd_perdas':
                    $strategy = new PddPerdasImporter($this->db);
                    break;
                case 'pdd_pagos':
                    $strategy = new PddPagosImporter($this->db);
                    break;
                case 'spc_excluidos':
                    $strategy = new SpcExcluidosImporter($this->db);
                    break;
                default:
                    throw new \Exception("Tipo de importação desconhecido: $tipo");
            }

            $strategy->import($arquivo, $batchId);
            
            // Atualiza status para sucesso (já é default, mas ok)
            
        } catch (\Exception $e) {
            // Atualiza status para erro
            $stmtErr = $this->db->prepare("UPDATE import_batches SET status = 'error' WHERE id = ?");
            $stmtErr->execute([$batchId]);
            throw $e;
        }

        return $batchId;
    }
}
