<?php

namespace App\Helpers;

class Normalizer {
    public static function cpfCnpj($value) {
        // Remove tudo que não é dígito
        return preg_replace('/[^0-9]/', '', (string)$value);
    }

    public static function contrato($value) {
        // Remove espaços extras, converte para maiúsculo e remove zeros à esquerda
        $value = strtoupper(trim((string)$value));
        return ltrim($value, '0');
    }

    public static function data($value) {
        if (empty($value)) return null;
        
        try {
            // Se for numérico (Excel timestamp)
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            
            // Tenta converter string PT-BR (dd/mm/yyyy)
            if (strpos($value, '/') !== false) {
                $parts = explode('/', $value);
                if (count($parts) === 3) {
                    $day = (int)$parts[0];
                    $month = (int)$parts[1];
                    $year = (int)$parts[2];
                    
                    // Fix year 00XX or XX -> 20XX
                    if ($year < 100) {
                        $year += 2000;
                    } elseif ($year < 1000) {
                        // Case 0025 -> 25 -> 2025
                        // But intval('0025') is 25.
                        // If it was '0025', intval is 25.
                        $year += 2000;
                    }

                    if (checkdate($month, $day, $year)) {
                        return sprintf('%04d-%02d-%02d', $year, $month, $day);
                    }
                }
            }
            
            // Tenta converter Y-m-d direto ou outros formatos
            // Fix for 0025-MM-DD
            if (preg_match('/^00(\d{2})-(\d{2})-(\d{2})$/', $value, $matches)) {
                $year = 2000 + (int)$matches[1];
                return sprintf('%04d-%02d-%02d', $year, $matches[2], $matches[3]);
            }

            $timestamp = strtotime($value);
            if ($timestamp !== false && $timestamp > 0) {
                $year = (int)date('Y', $timestamp);
                // Fix if strtotime parsed as year 0025
                if ($year < 1000) {
                     $year += 2000;
                     return sprintf('%04d-%s', $year, date('m-d', $timestamp));
                }
                return date('Y-m-d', $timestamp);
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    public static function valor($value) {
        if (is_string($value)) {
            $value = str_replace(['R$', ' '], '', $value);
            if (strpos($value, ',') !== false) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
        }
        return (float) $value;
    }
}
