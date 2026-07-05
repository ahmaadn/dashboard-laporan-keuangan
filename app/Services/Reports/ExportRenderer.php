<?php

namespace App\Services\Reports;

use App\Services\Mock\MockData;

/**
 * Builds export payloads (PDF view data + Excel spreadsheet markup) for the
 * financial report page. Both exports reuse {@see MockData::reportSummary()}
 * so they stay consistent with the on-screen report.
 */
final class ExportRenderer
{
    /**
     * Resolve the report summary for the current request parameters.
     *
     * @return array<string, mixed>
     */
    public function report(string $period, ?string $start, ?string $end): array
    {
        return MockData::reportSummary($period, $start, $end);
    }

    /**
     * Build an Excel-compatible SpreadsheetML 2003 document (.xls) without
     * requiring phpspreadsheet. Opens cleanly in Excel, LibreOffice & Numbers.
     */
    public function excel(array $report): string
    {
        $cells = [];

        $cells[] = $this->row(['Laporan Keuangan']);
        $cells[] = $this->row(['Periode', $report['rangeLabel'], '', '', '']);
        $cells[] = $this->row([]);
        $cells[] = $this->row(['Total Pemasukan', $report['totalIncome'], '', '', '']);
        $cells[] = $this->row(['Total Pengeluaran', $report['totalExpense'], '', '', '']);
        $cells[] = $this->row(['Laba / Rugi', $report['profit'], '', '', '']);
        $cells[] = $this->row([]);

        $cells[] = $this->row(['Pemasukan per Produk', '', '', '', '']);
        $cells[] = $this->row(['Produk', 'Jumlah Terjual', 'Transaksi', 'Total', '']);
        foreach ($report['incomeByProduct'] as $row) {
            $cells[] = $this->row([
                $row['nama'],
                (int) $row['qty'],
                (int) $row['count'],
                (int) $row['total'],
                '',
            ]);
        }
        $cells[] = $this->row([]);

        $cells[] = $this->row(['Pengeluaran per Kategori', '', '', '', '']);
        $cells[] = $this->row(['Kategori', 'Transaksi', 'Total', '', '']);
        foreach ($report['expenseByCategory'] as $row) {
            $cells[] = $this->row([
                $row['nama'],
                (int) $row['count'],
                (int) $row['total'],
                '',
                '',
            ]);
        }

        $rows = collect($cells)
            ->map(fn (array $cells) => '<Row>'.collect($cells)->map(fn ($c) => $this->cell($c))->implode('').'</Row>')
            ->implode("\n");

        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel">
<Styles>
<Style ss:ID="h"><Font ss:Bold="1"/></Style>
<Style ss:ID="num"><NumberFormat ss:Format="#,##0"/></Style>
</Styles>
<Worksheet ss:Name="Laporan">
<Table>
XML
            .$rows."\n</Table>\n</Worksheet>\n</Workbook>\n";
    }

    /**
     * @param  list<string|int|float>  $values
     * @return array<int, string>
     */
    private function row(array $values): array
    {
        return array_map(fn ($v) => is_string($v) && $v === '' ? '' : (string) $v, $values);
    }

    private function cell(string|int $value): string
    {
        $isNumeric = is_int($value) || (is_string($value) && $value !== '' && ctype_digit($value));

        return '<Cell'
            .($isNumeric ? ' ss:StyleID="num"' : ' ss:StyleID="h"')
            .'><Data ss:Type="'.($isNumeric ? 'Number' : 'String').'">'.$this->escape((string) $value).'</Data></Cell>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
