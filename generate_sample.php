<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header row (23 columns - EXACTLY as required)
$headers = [
    'CONTRATO', 'TP_CONTRATO', 'CONTRATANTE', 'CONTRATACAO', 'CPF_CNPJ', 'STATUS',
    'VENDA', 'PARCELA', 'DEBITO', 'EMISSAO', 'VENCIMENTO', 'DIAS_ATRASO',
    'RUA', 'NUMERO', 'BAIRRO', 'CEP', 'CIDADE', 'ESTADO',
    'NASCIMENTO', 'LINHA', 'STATUS INCLUSAO', 'DATA INCLUSAO', 'HORA INCLUSAO'
];

$sheet->fromArray($headers, NULL, 'A1');

// Sample Data Row 1 (Active)
$data1 = [
    '123456', 'Empréstimo', 'João da Silva', '01/01/2023', '123.456.789-00', 'A',
    'Venda Online', '1/10', '150.00', '01/01/2023', '10/01/2023', '30',
    'Rua das Flores', '100', 'Centro', '12345-000', 'São Paulo', 'SP',
    '01/01/1980', 'Linha 1', 'Incluído', '15/02/2023', '14:30:00'
];

// Sample Data Row 2 (Suspended)
$data2 = [
    '654321', 'Financiamento', 'Empresa XYZ', '01/06/2023', '12.345.678/0001-99', 'S',
    'Loja Física', '5/24', '2500.50', '01/06/2023', '15/06/2023', '15',
    'Av. Paulista', '2000', 'Bela Vista', '01310-100', 'São Paulo', 'SP',
    '15/05/1995', 'Linha 2', 'Pendente', '20/06/2023', '09:00:00'
];

// Sample Data Row 3 (Rescinded)
$data3 = [
    '789012', 'Consórcio', 'Maria Oliveira', '10/03/2023', '987.654.321-11', 'R',
    'Telemarketing', '2/12', '500.00', '10/03/2023', '20/03/2023', '5',
    'Rua Augusta', '500', 'Consolação', '01305-000', 'São Paulo', 'SP',
    '20/10/1985', 'Linha 3', 'Erro', '25/03/2023', '11:15:00'
];

$sheet->fromArray($data1, NULL, 'A2');
$sheet->fromArray($data2, NULL, 'A3');
$sheet->fromArray($data3, NULL, 'A4');

// Auto-size columns
foreach (range('A', 'W') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'public/modelo_spc_inclusos_v2.xlsx';

// Ensure directory exists
if (!is_dir('public')) {
    mkdir('public', 0777, true);
}

$writer->save($filename);

echo "Arquivo de modelo gerado com sucesso: $filename\n";
