<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Importer.php';
require_once __DIR__ . '/../src/Comparator.php';

use App\Importer;
use App\Comparator;

$database = new Database();
$db = $database->getConnection();

$page = $_GET['page'] ?? 'dashboard';
$message = '';

// DEBUG GLOBAL
// DEBUG GLOBAL
// file_put_contents('debug_global_request.log', date('Y-m-d H:i:s') . " - Page: $page - URI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);
error_log("Page: $page - URI: " . $_SERVER['REQUEST_URI']);

// 0. Handle Admin Actions (JSON) - MUST BE BEFORE HTML OUTPUT
if ($page === 'admin_action') {
    $log = "Admin Action Request: " . print_r($_GET, true) . "\n";
    $log = "Admin Action Request: " . print_r($_GET, true);
    error_log($log);

    require_once '../src/AdminController.php';
    $admin = new \App\AdminController($db);
    
    $action = $_GET['action'] ?? '';
    $table = $_GET['table'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    // Clear buffer to ensure clean JSON
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    if ($action === 'delete') {
        try {
            $success = $admin->delete($table, $id);
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } elseif ($action === 'update_cell') {
        $column = $_POST['column'] ?? '';
        $value = $_POST['value'] ?? '';
        $id = $_POST['id'] ?? 0;
        
        try {
            $success = $admin->updateCell($table, $id, $column, $value);
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } elseif ($action === 'get_column_values') {
        $column = $_GET['column'] ?? '';
        try {
            $values = $admin->getColumnValues($table, $column);
            echo json_encode(['success' => true, 'values' => $values]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    exit;
}

// 0.1 Handle Admin Export
if ($page === 'admin_export') {
    // Desativar erros na tela
    error_reporting(0);
    ini_set('display_errors', 0);

    // Limpa buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    require_once '../src/AdminController.php';
    $admin = new \App\AdminController($db);
    $table = $_GET['table'] ?? '';
    $search = $_GET['search'] ?? '';
    $filters = $_GET['filter'] ?? [];

    try {
        $data = $admin->getExportData($table, $search, $filters);
        
        if (empty($data)) {
            die("Nenhum dado encontrado para exportar.");
        }

        // --- FIX FOR SPC_INCLUSOS ---
        // Dynamically populate LINHA and NASCIMENTO for Admin Export
        if ($table === 'spc_inclusos') {
            foreach ($data as $index => &$item) {
                // Populate LINHA (Excel Row = Index + 2)
                $item['linha'] = $index + 2;
                
                // Populate NASCIMENTO if empty or invalid
                if (empty($item['nascimento']) || $item['nascimento'] === '0000-00-00') {
                    $item['nascimento'] = '01/01/1980';
                } else {
                    // Ensure date format DD/MM/YYYY
                    $ts = strtotime($item['nascimento']);
                    if ($ts) {
                        $item['nascimento'] = date('d/m/Y', $ts);
                    }
                }
            }
            unset($item); // Break reference
        }
        // ----------------------------

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($table, 0, 31)); // Excel limit 31 chars

        // Headers
        $headers = array_keys($data[0]);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', strtoupper($header));
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Data
        $row = 2;
        foreach ($data as $item) {
            $col = 'A';
            foreach ($item as $key => $value) {
                // Force String for sensitive/numeric-like columns to avoid scientific notation
                if (in_array($key, ['cpf_cnpj', 'cpf_cnpj_norm', 'contrato', 'cnpj', 'cpf'])) {
                    $sheet->setCellValueExplicit($col . $row, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($col . $row, $value);
                }
                $col++;
            }
            $row++;
        }

        // Generate File
        $filename = 'export_' . $table . '_' . date('Y-m-d_His') . '.xlsx';
        
        // Save to temporary file
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        // Serve file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        readfile($tempPath);
        unlink($tempPath); // cleanup
        exit;

    } catch (Exception $e) {
        die("Erro na exportação: " . $e->getMessage());
    }
}

// 1. Handle Export FIRST
// 1. Handle Export FIRST
if ($page === 'export') {
    // Desativar erros na tela para não corromper o binário
    error_reporting(0);
    ini_set('display_errors', 0);

    // Limpa qualquer buffer de saída anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $comparator = new Comparator($db);
    $exclusao = $comparator->obterParaExclusao();
    $inclusao = $comparator->obterParaInclusao();
    
    // Split Inclusao into CPF and CNPJ
    $inclusaoCPF = [];
    $inclusaoCNPJ = [];
    
    foreach ($inclusao as $item) {
        // Simple logic: if length > 11 digits, it's CNPJ (usually 14).
        // Remove non-digits for check
        $digits = preg_replace('/\D/', '', $item['cpf_cnpj']);
        if (strlen($digits) > 11) {
            $inclusaoCNPJ[] = $item;
        } else {
            $inclusaoCPF[] = $item;
        }
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

    // --- SHEET 1: INCLUIR CPF ---
    $sheet1 = $spreadsheet->getActiveSheet();
    $sheet1->setTitle('INCLUIR CPF');
    $headers1 = ['LINHA', 'CPF', 'NOME', 'CONTRATO', 'VENCIMENTO', 'VALOR', 'NASCIMENTO', 'ENDERECO'];
    $col = 'A';
    foreach ($headers1 as $h) {
        $sheet1->setCellValue($col . '1', $h);
        $col++;
    }

    $row = 2;
    $linha = 2; // Starts at 2 as requested
    foreach ($inclusaoCPF as $item) {
        $sheet1->setCellValue('A' . $row, $linha);
        // Force String for CPF
        $sheet1->setCellValueExplicit('B' . $row, $item['cpf_cnpj'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet1->setCellValue('C' . $row, $item['contratante'] ?? $item['nome'] ?? '');
        $sheet1->setCellValueExplicit('D' . $row, $item['contrato'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $vencimento = $item['vencimento'] ?? $item['data_vencimento'] ?? null;
        $sheet1->setCellValue('E' . $row, $vencimento ? date('d/m/Y', strtotime($vencimento)) : '');
        
        $sheet1->setCellValue('F' . $row, number_format($item['debito'] ?? $item['valor'], 2, ',', ''));
        
        // Nascimento Fictício
        $sheet1->setCellValue('G' . $row, '01/01/1980');
        
        $sheet1->setCellValue('H' . $row, ($item['rua'] ?? '') . ', ' . ($item['numero'] ?? ''));
        
        $row++;
        $linha++;
    }
    
    foreach (range('A', 'H') as $col) {
        $sheet1->getColumnDimension($col)->setAutoSize(true);
    }

    // --- SHEET 2: INCLUIR CNPJ ---
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('INCLUIR CNPJ');
    $headers2 = ['LINHA', 'CNPJ', 'NOME', 'CONTRATO', 'VENCIMENTO', 'VALOR', 'ENDERECO'];
    $col = 'A';
    foreach ($headers2 as $h) {
        $sheet2->setCellValue($col . '1', $h);
        $col++;
    }

    $row = 2;
    $linha = 2;
    foreach ($inclusaoCNPJ as $item) {
        $sheet2->setCellValue('A' . $row, $linha);
        // Force String for CNPJ
        $sheet2->setCellValueExplicit('B' . $row, $item['cpf_cnpj'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet2->setCellValue('C' . $row, $item['contratante'] ?? $item['nome'] ?? '');
        $sheet2->setCellValueExplicit('D' . $row, $item['contrato'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $vencimento = $item['vencimento'] ?? $item['data_vencimento'] ?? null;
        $sheet2->setCellValue('E' . $row, $vencimento ? date('d/m/Y', strtotime($vencimento)) : '');
        
        $sheet2->setCellValue('F' . $row, number_format($item['debito'] ?? $item['valor'], 2, ',', ''));
        $sheet2->setCellValue('G' . $row, ($item['rua'] ?? '') . ', ' . ($item['numero'] ?? ''));
        
        $row++;
        $linha++;
    }

    foreach (range('A', 'G') as $col) {
        $sheet2->getColumnDimension($col)->setAutoSize(true);
    }

    // --- SHEET 3: EXCLUIR ---
    $sheet3 = $spreadsheet->createSheet();
    $sheet3->setTitle('Excluir');
    $headers3 = ['LINHA', 'CPF/CNPJ', 'CONTRATO', 'DATA INCLUSÃO', 'VALOR', 'MOTIVO'];
    $col = 'A';
    foreach ($headers3 as $h) {
        $sheet3->setCellValue($col . '1', $h);
        $col++;
    }

    $row = 2;
    $linha = 2;
    foreach ($exclusao as $item) {
        $sheet3->setCellValue('A' . $row, $linha);
        // Force String
        $sheet3->setCellValueExplicit('B' . $row, $item['cpf_cnpj'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet3->setCellValueExplicit('C' . $row, $item['contrato'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        
        $dataInclusao = $item['data_inclusao'] ?? null;
        $sheet3->setCellValue('D' . $row, $dataInclusao ? date('d/m/Y', strtotime($dataInclusao)) : '');
        
        $sheet3->setCellValue('E' . $row, number_format($item['valor_debito'] ?? $item['valor'], 2, ',', ''));
        $sheet3->setCellValue('F' . $row, $item['motivo']);
        $row++;
        $linha++;
    }

    foreach (range('A', 'F') as $col) {
        $sheet3->getColumnDimension($col)->setAutoSize(true);
    }
    
    $spreadsheet->setActiveSheetIndex(0);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_spc_fase2.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// 2. Handle Upload Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importer = new Importer($db);
    $processed = 0;
    
    try {
        if (!empty($_FILES['spc_file']['name'])) {
            $path = sys_get_temp_dir() . '/' . basename($_FILES['spc_file']['name']);
            move_uploaded_file($_FILES['spc_file']['tmp_name'], $path);
            $importer->importarArquivo($path, 'spc');
            $processed++;
        }

        if (!empty($_FILES['parcelas_file']['name'])) {
            $path = sys_get_temp_dir() . '/' . basename($_FILES['parcelas_file']['name']);
            move_uploaded_file($_FILES['parcelas_file']['tmp_name'], $path);
            $importer->importarArquivo($path, 'parcelas');
            $processed++;
        }
        
        if (!empty($_FILES['pdd_perdas_file']['name'])) {
            $path = sys_get_temp_dir() . '/' . basename($_FILES['pdd_perdas_file']['name']);
            move_uploaded_file($_FILES['pdd_perdas_file']['tmp_name'], $path);
            $importer->importarArquivo($path, 'pdd_perdas');
            $processed++;
        }
        
        if (!empty($_FILES['pdd_pagos_file']['name'])) {
            $path = sys_get_temp_dir() . '/' . basename($_FILES['pdd_pagos_file']['name']);
            move_uploaded_file($_FILES['pdd_pagos_file']['tmp_name'], $path);
            $importer->importarArquivo($path, 'pdd_pagos');
            $processed++;
        }

        if (!empty($_FILES['spc_excluidos_file']['name'])) {
            $path = sys_get_temp_dir() . '/' . basename($_FILES['spc_excluidos_file']['name']);
            move_uploaded_file($_FILES['spc_excluidos_file']['tmp_name'], $path);
            $importer->importarArquivo($path, 'spc_excluidos');
            $processed++;
        }

        if (!empty($_FILES['spc_atualizacao_file']['name'])) {
            $path = sys_get_temp_dir() . '/' . basename($_FILES['spc_atualizacao_file']['name']);
            move_uploaded_file($_FILES['spc_atualizacao_file']['tmp_name'], $path);
            $importer->importarArquivo($path, 'spc_atualizacao');
            $processed++;
        }

        if ($processed > 0) {
            header('Location: index.php?page=report&success=1');
            exit;
        } else {
            $message = "Nenhum arquivo selecionado.";
        }
    } catch (Exception $e) {
        $message = "Erro ao importar: " . $e->getMessage();
        $message = "Erro ao importar: " . $e->getMessage();
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    }
}

// DEBUG UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log = "POST Request received at " . date('Y-m-d H:i:s') . "\n";
    $log .= "FILES: " . print_r($_FILES, true) . "\n";
    $log .= "FILES: " . print_r($_FILES, true) . "\n";
    error_log($log);
}

// 3. Output HTML
include '../templates/header.php';

if ($page === 'dashboard') {
    include '../templates/dashboard.php';
} elseif ($page === 'admin') {
    require_once '../src/AdminController.php';
    $admin = new \App\AdminController($db);
    
    $table = $_GET['table'] ?? 'spc_inclusos';
    $p = $_GET['p'] ?? 1;
    $search = $_GET['search'] ?? '';
    $limit = $_GET['limit'] ?? 10;
    
    // Parse filters from GET
    $filters = [];
    if (isset($_GET['filter']) && is_array($_GET['filter'])) {
        $filters = $_GET['filter'];
    }

    // Validate limit
    $allowedLimits = [10, 50, 100, 'all'];
    if (!in_array($limit, $allowedLimits) && $limit !== 'all') {
        $limit = 10;
    }
    
    // Handle 'all' case for listTable (pass a very large number or handle inside controller)
    // For simplicity, let's pass a large number if 'all'
    $perPage = ($limit === 'all') ? 1000000 : (int)$limit;
    
    try {
        $result = $admin->listTable($table, $p, $perPage, $search, $filters);
        // Admin uses a different layout, so we don't include header/footer here or we customize them
        // For simplicity, let's use a standalone admin template
        include '../templates/admin.php';
        exit; // Stop here, don't include footer
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
} elseif ($page === 'report') {
    $comparator = new Comparator($db);
    
    // Auto-cleanup expired debts (5+ years)
    $comparator->limparPrescritos();
    
    // Auto-cleanup duplicate vendas
    $comparator->limparVendasDuplicadas();
    
    $exclusao = $comparator->obterParaExclusao();
    $inclusao = $comparator->obterParaInclusao();
    
    if (isset($_GET['success']) && $_GET['success'] == 1) {
        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Sucesso!</strong>
                <span class="block sm:inline">Arquivos importados e processados com sucesso.</span>
              </div>';
    }
    
    include '../templates/report.php';
} elseif ($page === 'maintenance') {
    include '../templates/maintenance.php';
} else {
    include '../templates/dashboard.php';
}

include '../templates/footer.php';
