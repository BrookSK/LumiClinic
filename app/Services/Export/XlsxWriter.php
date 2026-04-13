<?php

declare(strict_types=1);

namespace App\Services\Export;

/**
 * Minimal XLSX writer — generates a valid .xlsx file without external dependencies.
 * Supports: bold headers, column widths, number/currency formatting, date formatting.
 */
final class XlsxWriter
{
    /** @var list<list<mixed>> */
    private array $rows = [];
    /** @var list<string> */
    private array $headers = [];
    /** @var array<int,float> col index => width */
    private array $colWidths = [];
    /** @var array<int,string> col index => format: 'text','number','currency','date' */
    private array $colFormats = [];
    private string $sheetName = 'Dados';

    public function setSheetName(string $name): self { $this->sheetName = $name; return $this; }

    /** @param list<string> $headers */
    public function setHeaders(array $headers): self { $this->headers = $headers; return $this; }

    /** @param array<int,string> $formats col index => 'text'|'number'|'currency'|'date' */
    public function setColumnFormats(array $formats): self { $this->colFormats = $formats; return $this; }

    /** @param list<mixed> $row */
    public function addRow(array $row): self { $this->rows[] = $row; return $this; }

    /** @param list<list<mixed>> $rows */
    public function addRows(array $rows): self { foreach ($rows as $r) $this->rows[] = $r; return $this; }

    public function generate(): string
    {
        $this->calcWidths();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->wbRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/sharedStrings.xml', $this->sharedStrings());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet());

        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);
        return $bytes !== false ? $bytes : '';
    }

    private function calcWidths(): void
    {
        $allRows = $this->headers !== [] ? array_merge([$this->headers], $this->rows) : $this->rows;
        foreach ($allRows as $row) {
            foreach ($row as $ci => $val) {
                $len = mb_strlen((string)$val, 'UTF-8');
                $w = max(8, min(50, $len + 2));
                if (!isset($this->colWidths[$ci]) || $w > $this->colWidths[$ci]) {
                    $this->colWidths[$ci] = (float)$w;
                }
            }
        }
    }

    /** @var list<string> */
    private array $strings = [];
    /** @var array<string,int> */
    private array $stringIndex = [];

    private function si(string $s): int
    {
        if (isset($this->stringIndex[$s])) return $this->stringIndex[$s];
        $idx = count($this->strings);
        $this->strings[] = $s;
        $this->stringIndex[$s] = $idx;
        return $idx;
    }

    private function colLetter(int $i): string
    {
        $l = '';
        $i++;
        while ($i > 0) { $i--; $l = chr(65 + ($i % 26)) . $l; $i = intdiv($i, 26); }
        return $l;
    }

    private function sheet(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $x .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Column widths
        if ($this->colWidths !== []) {
            $x .= '<cols>';
            foreach ($this->colWidths as $ci => $w) {
                $cn = $ci + 1;
                $x .= '<col min="' . $cn . '" max="' . $cn . '" width="' . number_format($w, 2, '.', '') . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }

        $x .= '<sheetData>';
        $rowNum = 1;

        // Header row (style 1 = bold)
        if ($this->headers !== []) {
            $x .= '<row r="' . $rowNum . '">';
            foreach ($this->headers as $ci => $h) {
                $ref = $this->colLetter($ci) . $rowNum;
                $si = $this->si((string)$h);
                $x .= '<c r="' . $ref . '" t="s" s="1"><v>' . $si . '</v></c>';
            }
            $x .= '</row>';
            $rowNum++;
        }

        // Data rows
        foreach ($this->rows as $row) {
            $x .= '<row r="' . $rowNum . '">';
            foreach ($row as $ci => $val) {
                $ref = $this->colLetter($ci) . $rowNum;
                $fmt = $this->colFormats[$ci] ?? 'text';

                if ($fmt === 'currency' || $fmt === 'number') {
                    $numVal = is_numeric($val) ? (float)$val : 0;
                    $styleId = $fmt === 'currency' ? '2' : '3';
                    $x .= '<c r="' . $ref . '" s="' . $styleId . '"><v>' . $numVal . '</v></c>';
                } else {
                    $si = $this->si((string)$val);
                    $x .= '<c r="' . $ref . '" t="s" s="0"><v>' . $si . '</v></c>';
                }
            }
            $x .= '</row>';
            $rowNum++;
        }

        $x .= '</sheetData>';
        $x .= '</worksheet>';
        return $x;
    }

    private function sharedStrings(): string
    {
        // Pre-populate strings from all cells
        if ($this->headers !== []) foreach ($this->headers as $h) $this->si((string)$h);
        foreach ($this->rows as $row) {
            foreach ($row as $ci => $val) {
                $fmt = $this->colFormats[$ci] ?? 'text';
                if ($fmt !== 'currency' && $fmt !== 'number') $this->si((string)$val);
            }
        }

        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $x .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($this->strings) . '" uniqueCount="' . count($this->strings) . '">';
        foreach ($this->strings as $s) {
            $x .= '<si><t>' . htmlspecialchars($s, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $x .= '</sst>';
        return $x;
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="R$ #,##0.00"/></numFmts>'
            . '<fonts count="2">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF815901"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                          // 0: normal
            .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>' // 1: header (bold white on gold)
            .   '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'   // 2: currency R$
            .   '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'     // 3: number
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private function workbook(): string
    {
        $name = htmlspecialchars($this->sheetName, ENT_XML1, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function wbRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';
    }
}
