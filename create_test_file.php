<?php
// Create a test Excel file with correct structure for SPC Inclusos
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header row (23 columns as per the image)
$headers = [
    'CONTRATO', 'TP_CONTRATO', 'CONTRATANTE', 'CONTRATACAO', 'CPF_CNPJ', 'STATUS',
    'VENDA', 'PARCELA', 'DEBITO', 'EMISSAO', 'VENCIMENTO', 'DIAS_ATRASO',
    'RUA', 'NUMERO', 'BAIRRO', 'CEP', 'CIDADE', 'ESTADO',
    'NASCIMENTO', 'LINHA', 'STATUS INCLUSAO', 'DATA INCLUSAO', 'HORA INCLUSAO'
];

$sheet->fromArray($headers, NULL, 'A1');

// Add one test row
$testData = [
    '12345', 'TIPO1', 'EMPRESA TESTE', '2024-01-01', '12345678901', 'ATIVO',
    'VENDA1', '1/12', '1000.00', '2024-01-01', '2024-02-01', '30',
    'RUA TESTE', '123', 'BAIRRO TESTE', '12345-678', 'SAO PAULO', 'SP',
    '1990-01-01', 'LINHA1', 'INCLUIDO', '2024-01-15', '10:30:00'
];

$sheet->fromArray($testData, NULL, 'A2');

$writer = new Xlsx($spreadsheet);
$filename = 'public/uploads/test_spc.xlsx';

// Create uploads directory if it doesn't exist
if (!is_dir('public/uploads')) {
    mkdir('public/uploads', 0777, true);
}

$writer->save($filename);

echo "✓ Test file created: $filename\n";
echo "  Columns: " . count($headers) . "\n";
echo "  Test rows: 1\n";
