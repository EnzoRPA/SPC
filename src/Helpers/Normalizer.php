<?php

namespace App\Helpers;

class Normalizer {
    public static function cpfCnpj($value) {
        // Remove tudo que não é dígito
        return preg_replace('/[^0-9]/', '', (string)$value);
    }

    public static function contrato($value) {
        // Remove ALL spaces (not just trim), convert to uppercase, remove leading zeros
        $value = strtoupper(trim((string)$value));
        $value = str_replace(' ', '', $value); // Remove ALL spaces
        return ltrim($value, '0');
    }

    public static function data($value) {
        if (empty($value)) return null;
        
        try {
            // Se for numérico (Excel timestamp)
            if (is_numeric($value)) {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                $year = (int)$dt->format('Y');
                if ($year < 1900 || $year > 2200) return null;
                return $dt->format('Y-m-d');
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

                    if ($year < 1900 || $year > 2200) return null;

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
                     // Re-check validity after adjustment
                     if ($year < 1900 || $year > 2200) return null;
                     return sprintf('%04d-%s', $year, date('m-d', $timestamp));
                }
                
                // Validate year range
                if ($year < 1900 || $year > 2200) {
                    return null;
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
            // Remove non-breaking spaces and other whitespace
            $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $value);
            $value = str_replace(['R$', ' ', 'r$', ' '], '', $value); // Remove basics
            
            // Check for Brazilian format (1.234,56) vs International (1,234.56)
            // Heuristic: if last punctuation is comma, it's likely decimal separator for BR
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');
            
            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                // Brazilian format: remove dots, replace comma with dot
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } elseif ($lastDot !== false && ($lastComma === false || $lastDot > $lastComma)) {
                // International format: remove commas
                $value = str_replace(',', '', $value);
            }
        }
        return (float) $value;
    }
    
    // Alias methods for compatibility
    public static function normalizarContrato($value) {
        return self::contrato($value);
    }
    
    public static function normalizarCpfCnpj($value) {
        return self::cpfCnpj($value);
    }
}
