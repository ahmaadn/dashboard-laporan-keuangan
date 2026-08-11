<?php

namespace App\Services\Reports;

use App\Services\ReportService;

/**
 * Builds export payloads (PDF view data + Excel spreadsheet markup) for the
 * financial report page. Both exports reuse {@see ReportService::summary()}
 * so they stay consistent with the on-screen report.
 */
final class ExportRenderer
{
    public function __construct(private readonly ReportService $reportService) {}

    /** @return array<string, mixed> */
    public function report(string $period, ?string $start, ?string $end): array
    {
        return $this->reportService->summary($period, $start, $end);
    }

    /** Build an Excel-compatible SpreadsheetML 2003 document (.xls). */
    public function excel(array $report): string
    {
        $cells = [];

        $cells[] = $this->row(['Laporan Laba Rugi']);
        $cells[] = $this->row(['Periode', $report['rangeLabel'], '', '', '']);
        $cells[] = $this->row([]);
        $cells[] = $this->row(['Pendapatan Bersih', (int) ($report['pendapatanBersih'] ?? 0), '', '', '']);
        $cells[] = $this->row(['Penjualan (kotor)', (int) $report['penjualan'], '', '', '']);
        $cells[] = $this->row(['Retur Penjualan', (int) ($report['returTotal'] ?? 0), '', '', '']);
        $cells[] = $this->row(['HPP', (int) $report['hpp'], '', '', '']);
        $cells[] = $this->row(['Laba Kotor', (int) $report['labaKotor'], '', '', '']);
        $cells[] = $this->row(['Beban Operasional', (int) $report['biayaOperasional'], '', '', '']);
        $cells[] = $this->row(['Laba Bersih', (int) $report['labaBersih'], '', '', '']);
        $cells[] = $this->row(['Modal / Setoran', (int) ($report['modalTotal'] ?? 0), '', '', '']);
        $cells[] = $this->row(['Pengeluaran Kas (semua)', (int) $report['pengeluaranKas'], '', '', '']);
        $cells[] = $this->row(['Arus Kas Bersih (bukan laba)', (int) ($report['arusKasBersih'] ?? 0), '', '', '']);
        $cells[] = $this->row([]);

        $cells[] = $this->row(['Pemasukan per Produk', '', '', '', '', '']);
        $cells[] = $this->row(['Produk', 'Qty Bersih', 'Qty Retur', 'Retur', 'HPP', 'Total Bersih', 'Laba Kotor']);
        foreach ($report['incomeByProduct'] as $row) {
            $cells[] = $this->row([
                $row['nama'],
                (int) ($row['net_qty'] ?? $row['qty']),
                (int) ($row['retur_qty'] ?? 0),
                (int) ($row['retur'] ?? 0),
                (int) ($row['hpp'] ?? 0),
                (int) ($row['net_total'] ?? $row['total']),
                (int) ($row['laba_kotor'] ?? 0),
            ]);
        }
        $cells[] = $this->row([]);

        $cells[] = $this->row(['Kanal Penjualan', '', '', '', '', '']);
        $cells[] = $this->row(['Kanal', 'Transaksi', 'Unit', 'Bruto', 'Retur', 'Bersih']);
        foreach (['online' => 'Online', 'offline' => 'Offline'] as $channelKey => $channelLabel) {
            $channel = $report['incomeByChannel'][$channelKey] ?? null;
            if ($channel === null) {
                continue;
            }
            $cells[] = $this->row([
                $channelLabel,
                (int) $channel['count'],
                (int) $channel['qty'],
                (int) $channel['total'],
                (int) $channel['retur'],
                (int) $channel['net_total'],
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

        $journal = $report['cashJournal'] ?? null;
        if ($journal !== null && count($journal['entries']) > 0) {
            $cells[] = $this->row([]);
            $cells[] = $this->row(['Jurnal Arus Kas Bersih', '', '', '', '', '']);
            $cells[] = $this->row(['Tanggal', 'Kategori', 'Keterangan', 'Masuk', 'Keluar', 'Saldo']);
            $cells[] = $this->row(['Saldo awal', '', '', '', '', (int) $journal['saldoAwal']]);
            foreach ($journal['entries'] as $entry) {
                $cells[] = $this->row([
                    (string) $entry['tanggal'],
                    (string) $entry['kategori'],
                    (string) $entry['keterangan'],
                    (int) $entry['masuk'],
                    (int) $entry['keluar'],
                    (int) $entry['saldo'],
                ]);
            }
            $cells[] = $this->row([
                'Total',
                '',
                '',
                (int) $journal['totalMasuk'],
                (int) $journal['totalKeluar'],
                (int) $journal['saldoAkhir'],
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
        $isNumeric = is_int($value) || (is_string($value) && $value !== '' && preg_match('/^-?\d+$/', $value));

        return '<Cell'
            .($isNumeric ? ' ss:StyleID="num"' : ' ss:StyleID="h"')
            .'><Data ss:Type="'.($isNumeric ? 'Number' : 'String').'">'.$this->escape((string) $value).'</Data></Cell>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
