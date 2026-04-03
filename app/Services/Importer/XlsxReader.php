<?php

declare(strict_types=1);

namespace App\Services\Importer;

/**
 * Minimal XLSX reader using PharData (no ext-zip required).
 * Returns rows as arrays of strings.
 */
final class XlsxReader
{
    /** @return list<list<string>> */
    public static function read(string $filePath, int $maxRows = 10000): array
    {
        $tmp = sys_get_temp_dir() . '/xlsx_' . md5($filePath . microtime(true)) . '.zip';
        copy($filePath, $tmp);

        try {
            $phar = new \PharData($tmp);

            // shared strings
            $strings = [];
            if (isset($phar['xl/sharedStrings.xml'])) {
                $sx = new \SimpleXMLElement($phar['xl/sharedStrings.xml']->getContent());
                foreach ($sx->si as $si) {
                    $t = '';
                    if ($si->t) {
                        $t = (string)$si->t;
                    } elseif ($si->r) {
                        foreach ($si->r as $r) {
                            $t .= (string)$r->t;
                        }
                    }
                    $strings[] = $t;
                }
            }

            // sheet1
            $sheetXml = $phar['xl/worksheets/sheet1.xml']->getContent();
            $sheet = new \SimpleXMLElement($sheetXml);

            $rows = [];
            $rowCount = 0;
            foreach ($sheet->sheetData->row as $row) {
                if ($rowCount >= $maxRows) break;

                $cells = [];
                $maxCol = 0;
                foreach ($row->c as $c) {
                    $ref = (string)$c['r'];
                    $colIndex = self::colToIndex($ref);
                    $type = (string)$c['t'];
                    $val = (string)$c->v;

                    if ($type === 's' && isset($strings[(int)$val])) {
                        $val = $strings[(int)$val];
                    } elseif ($type === 'inlineStr') {
                        $val = (string)$c->is->t;
                    }

                    while (count($cells) < $colIndex) {
                        $cells[] = '';
                    }
                    $cells[$colIndex] = $val;
                    if ($colIndex > $maxCol) $maxCol = $colIndex;
                }

                $rows[] = $cells;
                $rowCount++;
            }

            return $rows;
        } finally {
            @unlink($tmp);
        }
    }

    private static function colToIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', $cellRef, $m);
        $letters = $m[1] ?? 'A';
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * Convert Excel serial date to Y-m-d string.
     */
    public static function excelDateToString(string $val): ?string
    {
        $val = trim($val);
        // Already formatted as dd/mm/yyyy
        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $val)) {
            $parts = explode('/', $val);
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        // Format dd/mm/yy (2-digit year)
        if (preg_match('#^(\d{2})/(\d{2})/(\d{2})$#', $val, $m)) {
            $year = (int)$m[3];
            $year = $year >= 0 && $year <= 49 ? 2000 + $year : 1900 + $year;
            return $year . '-' . $m[2] . '-' . $m[1];
        }
        // Excel serial number
        if (is_numeric($val) && (float)$val > 30000 && (float)$val < 100000) {
            $unix = ((float)$val - 25569) * 86400;
            return date('Y-m-d', (int)$unix);
        }
        // Already Y-m-d
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $val)) {
            return $val;
        }
        return null;
    }

    /**
     * Convert Excel serial datetime to Y-m-d H:i:s string.
     */
    public static function excelDateTimeToString(string $val): ?string
    {
        if (is_numeric($val) && (float)$val > 30000) {
            $unix = ((float)$val - 25569) * 86400;
            return date('Y-m-d H:i:s', (int)$unix);
        }
        return self::excelDateToString($val);
    }

    /**
     * Parse Brazilian money string to float: "R$2.150,00" -> 2150.00
     */
    public static function parseMoney(string $val): float
    {
        $val = trim($val);
        $val = str_replace(['R$', ' ', '.'], '', $val);
        $val = str_replace(',', '.', $val);
        return (float)$val;
    }
}
