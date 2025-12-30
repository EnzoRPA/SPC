<?php
ob_start(); // Start buffer immediately

// Standalone export script to avoid output pollution
error_reporting(0);
ini_set('display_errors', 0);

require_once '../vendor/autoload.php';
require_once '../config/db.php';
require_once '../src/Comparator.php';

use App\Comparator;

// Clean buffer immediately
while (ob_get_level()) {
    ob_end_clean();
}

$database = new Database();
$db = $database->getConnection();

$comparator = new Comparator($db);

$startDate = $_GET['data_inicio'] ?? null;
$endDate = $_GET['data_fim'] ?? null;

// Validate dates
if ($startDate && !strtotime($startDate)) $startDate = null;
if ($endDate && !strtotime($endDate)) $endDate = null;

$exclusao = $comparator->obterParaExclusao($startDate, $endDate);
$inclusao = $comparator->obterParaInclusao($startDate, $endDate);

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

// Helper to split by type
function splitByCpfCnpj($data) {
    $cpf = [];
    $cnpj = [];
    foreach ($data as $item) {
        // Use normalized field if available, otherwise strip non-digits
        $val = $item['cpf_cnpj_norm'] ?? preg_replace('/\D/', '', $item['cpf_cnpj']);
        if (strlen($val) > 11) {
            $cnpj[] = $item;
        } else {
            $cpf[] = $item;
        }
    }
    return ['cpf' => $cpf, 'cnpj' => $cnpj];
}

$splitInclusao = splitByCpfCnpj($inclusao);
$splitExclusao = splitByCpfCnpj($exclusao);

// Helper to format date and fix year 00XX -> 20XX
function formatDate($date) {
    if (empty($date)) return '';
    $ts = strtotime($date);
    if (!$ts) return '';
    
    $year = date('Y', $ts);
    if ($year < 1000) {
        // Fix year 00XX -> 20XX
        // Assuming these are recent dates (2000+)
        $newYear = intval($year) + 2000;
        return date('d/m/', $ts) . $newYear;
    }
    
    return date('d/m/Y', $ts);
}

// Function to populate Incluir sheet
function populateIncluirSheet($sheet, $data, $title) {
    $sheet->setTitle($title);
    $headers = [
        'CONTRATO', 'TP_CONTRATO', 'CONTRATANTE', 'CONTRATACAO', 'CPF_CNPJ', 
        'STATUS', 'VENDA', 'PARCELA', 'DEBITO', 'EMISSAO', 
        'VENCIMENTO', 'DIAS_ATRASO', 'RUA', 'NUMERO', 'BAIRRO', 
        'CEP', 'CIDADE', 'ESTADO', 'Nascimento', 'LINHA', 
        'STATUS INCLUSAO', 'DATA INCLUSAO', 'HORA INCLUSAO', 'MOTIVO'
    ];

    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    $row = 2;
    $linha = 2;
    $isCpfSheet = (strpos($title, 'CPF') !== false);

    foreach ($data as $item) {
        $sheet->setCellValueExplicit('A' . $row, $item['contrato'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $row, $item['tp_contrato'] ?? '');
        $sheet->setCellValue('C' . $row, $item['contratante'] ?? $item['nome'] ?? '');
        
        $sheet->setCellValue('D' . $row, formatDate($item['contratacao'] ?? null));
        
        $sheet->setCellValueExplicit('E' . $row, $item['cpf_cnpj'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('F' . $row, $item['status'] ?? '');
        $sheet->setCellValue('G' . $row, $item['venda'] ?? '');
        $sheet->setCellValue('H' . $row, $item['parcela'] ?? '');
        
        $sheet->setCellValue('I' . $row, number_format($item['debito'] ?? $item['valor'] ?? 0, 2, ',', ''));
        
        $sheet->setCellValue('J' . $row, formatDate($item['emissao'] ?? null));
        
        $vencimento = $item['vencimento'] ?? $item['data_vencimento'] ?? null;
        $sheet->setCellValue('K' . $row, formatDate($vencimento));
        
        $sheet->setCellValue('L' . $row, $item['dias_atraso'] ?? '');
        $sheet->setCellValue('M' . $row, $item['rua'] ?? '');
        $sheet->setCellValue('N' . $row, $item['numero'] ?? '');
        $sheet->setCellValue('O' . $row, $item['bairro'] ?? '');
        $sheet->setCellValue('P' . $row, $item['cep'] ?? '');
        $sheet->setCellValue('Q' . $row, $item['cidade'] ?? '');
        $sheet->setCellValue('R' . $row, $item['estado'] ?? '');
        
        // Nascimento
        $nascimento = '';
        if ($isCpfSheet) {
            $nascimento = '01/01/1980';
            // If actual data exists, use it? User asked for fictitious value, but let's prefer real if valid?
            // User said: "INCLUIR UM VALOR FICTICIO". So hardcode it.
            if (!empty($item['nascimento']) && $item['nascimento'] !== '0000-00-00') {
                 $ts = strtotime($item['nascimento']);
                 if ($ts) $nascimento = date('d/m/Y', $ts);
            }
        }
        $sheet->setCellValue('S' . $row, $nascimento);
        
        // LINHA
        $sheet->setCellValue('T' . $row, $linha);
        
        $sheet->setCellValue('U' . $row, $item['status_inclusao'] ?? '');
        $sheet->setCellValue('V' . $row, formatDate($item['data_inclusao'] ?? null));
        $sheet->setCellValue('W' . $row, $item['hora_inclusao'] ?? '');
        $sheet->setCellValue('X' . $row, $item['motivo'] ?? 'EM ABERTO');
        
        $row++;
        $linha++;
    }

    foreach (range('A', 'X') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Function to populate Excluir sheet
function populateExcluirSheet($sheet, $data, $title) {
    $sheet->setTitle($title);
    $sheet->setCellValue('A1', 'LINHA');
    $sheet->setCellValue('B1', 'CPF/CNPJ');
    $sheet->setCellValue('C1', 'CONTRATO');
    $sheet->setCellValue('D1', 'DATA VENCIMENTO');
    $sheet->setCellValue('E1', 'VALOR');
    $sheet->setCellValue('F1', 'MOTIVO');

    $row = 2;
    $linha = 2;
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $row, $linha);
        $sheet->setCellValueExplicit('B' . $row, $item['cpf_cnpj'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $row, $item['contrato'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $vencimento = $item['vencimento'] ?? null;
        $sheet->setCellValue('D' . $row, formatDate($vencimento));
        $sheet->setCellValue('E' . $row, number_format($item['valor_debito'] ?? $item['valor'], 2, ',', ''));
        $sheet->setCellValue('F' . $row, $item['motivo']);
        $row++;
        $linha++;
    }

    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Create Sheets
// Sheet 1: Incluir CPF
$sheet1 = $spreadsheet->getActiveSheet();
populateIncluirSheet($sheet1, $splitInclusao['cpf'], 'Incluir CPF');

// Sheet 2: Incluir CNPJ
$sheet2 = $spreadsheet->createSheet();
populateIncluirSheet($sheet2, $splitInclusao['cnpj'], 'Incluir CNPJ');

// Sheet 3: Excluir CPF
$sheet3 = $spreadsheet->createSheet();
populateExcluirSheet($sheet3, $splitExclusao['cpf'], 'Excluir CPF');

// Sheet 4: Excluir CNPJ
$sheet4 = $spreadsheet->createSheet();
populateExcluirSheet($sheet4, $splitExclusao['cnpj'], 'Excluir CNPJ');

$spreadsheet->setActiveSheetIndex(0);

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

// Save to public exports directory
$filename = 'relatorio_spc_fase2_' . date('Y-m-d_H-i-s') . '.xlsx';
$exportDir = __DIR__ . '/exports/';
if (!file_exists($exportDir)) {
    mkdir($exportDir, 0777, true);
}
$filePath = $exportDir . $filename;

$writer->save($filePath);

// Clear buffer again just in case
if (ob_get_length()) ob_end_clean();

// Redirect to the file
header("Location: exports/$filename");
exit;
