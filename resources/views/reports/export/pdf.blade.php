<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Laba Rugi — {{ config('app.name', 'BM Leather') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; margin: 32px; }
        .ld-print__head { margin-bottom: 18px; }
        .ld-print__brand { font-size: 16px; font-weight: 600; color: #111827; }
        .ld-print__title { font-size: 19px; font-weight: 600; margin-top: 10px; }
        .ld-print__meta { color: #6b7280; margin-top: 4px; font-size: 11px; }
        .ld-print__summary { width: 100%; margin-top: 18px; border-collapse: collapse; }
        .ld-print__summary td { border: 1px solid #e5e7eb; padding: 10px 12px; width: 25%; }
        .ld-print__summary .label { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        .ld-print__summary .value { font-size: 15px; font-weight: 600; margin-top: 4px; }
        .ld-print__summary .profit { color: #047857; }
        .ld-print__summary .loss { color: #b91c1c; }
        section { margin-top: 20px; }
        h2 { font-size: 13px; margin-bottom: 6px; color: #374151; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { text-align: left; padding: 7px 9px; border-bottom: 1px solid #e5e7eb; }
        thead th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #4b5563; }
        .num { text-align: right; }
        .fw-medium { font-weight: 500; }
        table.data .profit { color: #047857; }
        table.data .loss { color: #b91c1c; }
        tfoot td { border-top: 1px solid #9ca3af; font-weight: 600; }
        .ld-print__note { color: #6b7280; font-size: 10px; margin-top: 5px; }
        .ld-print__foot { margin-top: 24px; color: #9ca3af; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="ld-print__head">
        <div class="ld-print__brand">{{ config('app.name', 'BM Leather') }}</div>
        <div class="ld-print__title">Laporan Laba Rugi</div>
        <div class="ld-print__meta">Periode: {{ $report['rangeLabel'] }}</div>
    </div>

    <table class="ld-print__summary">
        <tr>
            <td>
                <div class="label">Pendapatan Bersih</div>
                <div class="value">{{ \App\Support\Format::rupiah($report['pendapatanBersih']) }}</div>
            </td>
            <td>
                <div class="label">HPP</div>
                <div class="value">{{ \App\Support\Format::rupiah($report['hpp']) }}</div>
            </td>
            <td>
                <div class="label">Laba Kotor</div>
                <div class="value {{ $report['labaKotor'] >= 0 ? 'profit' : 'loss' }}">{{ \App\Support\Format::rupiah($report['labaKotor']) }}</div>
            </td>
            <td>
                <div class="label">Laba Bersih</div>
                <div class="value {{ $report['labaBersih'] >= 0 ? 'profit' : 'loss' }}">{{ \App\Support\Format::rupiah($report['labaBersih']) }}</div>
            </td>
        </tr>
    </table>

    <section>
        <h2>Struktur Laba Rugi</h2>
        <table class="data">
            <tbody>
                <tr>
                    <td>Penjualan (kotor)</td>
                    <td class="num">{{ \App\Support\Format::rupiah($report['penjualan']) }}</td>
                </tr>
                <tr>
                    <td>− Retur Penjualan</td>
                    <td class="num loss">{{ \App\Support\Format::rupiah($report['returTotal']) }}</td>
                </tr>
                <tr>
                    <td class="fw-medium">= Pendapatan Bersih</td>
                    <td class="num fw-medium">{{ \App\Support\Format::rupiah($report['pendapatanBersih']) }}</td>
                </tr>
                <tr>
                    <td>− HPP (produk terjual)</td>
                    <td class="num loss">{{ \App\Support\Format::rupiah($report['hppPenjualan']) }}</td>
                </tr>
                @if ($report['hppPenyesuaianTotal'] != 0)
                    <tr>
                        <td>−/+ Penyesuaian HPP</td>
                        <td class="num">{{ \App\Support\Format::rupiah($report['hppPenyesuaianTotal']) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="fw-medium">= Laba Kotor</td>
                    <td class="num fw-medium">{{ \App\Support\Format::rupiah($report['labaKotor']) }}</td>
                </tr>
                <tr>
                    <td>− Beban Operasional</td>
                    <td class="num loss">{{ \App\Support\Format::rupiah($report['biayaOperasional']) }}</td>
                </tr>
                <tr>
                    <td class="fw-medium">= Laba Bersih</td>
                    <td class="num fw-medium {{ $report['labaBersih'] >= 0 ? 'profit' : 'loss' }}">{{ \App\Support\Format::rupiah($report['labaBersih']) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Kanal Penjualan</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Kanal</th>
                    <th class="num">Transaksi</th>
                    <th class="num">Unit</th>
                    <th class="num">Bruto</th>
                    <th class="num">Retur</th>
                    <th class="num">Bersih</th>
                </tr>
            </thead>
            <tbody>
                @foreach (['online' => 'Online', 'offline' => 'Offline'] as $channelKey => $channelLabel)
                    @php $channel = $report['incomeByChannel'][$channelKey]; @endphp
                    <tr>
                        <td class="fw-medium">{{ $channelLabel }}</td>
                        <td class="num">{{ $channel['count'] }}</td>
                        <td class="num">{{ $channel['qty'] }}</td>
                        <td class="num">{{ \App\Support\Format::rupiah($channel['total']) }}</td>
                        <td class="num loss">{{ $channel['retur'] > 0 ? \App\Support\Format::rupiah($channel['retur']) : '—' }}</td>
                        <td class="num fw-medium">{{ \App\Support\Format::rupiah($channel['net_total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if ($report['incomeByProduct'])
        <section>
            <h2>Pemasukan per Produk</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="num">Qty Bersih</th>
                        <th class="num">Retur</th>
                        <th class="num">HPP</th>
                        <th class="num">Total Bersih</th>
                        <th class="num">Laba Kotor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['incomeByProduct'] as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['nama'] }}</td>
                            <td class="num">
                                {{ $row['net_qty'] ?? $row['qty'] }}{{ ($row['retur_qty'] ?? 0) > 0 ? ' (dari '.$row['qty'].')' : '' }}
                            </td>
                            <td class="num loss">
                                {{ ($row['retur'] ?? 0) > 0 ? \App\Support\Format::rupiah($row['retur']).' / '.($row['retur_qty'] ?? 0).' unit' : '—' }}
                            </td>
                            <td class="num">{{ \App\Support\Format::rupiah($row['hpp'] ?? 0) }}</td>
                            <td class="num fw-medium">{{ \App\Support\Format::rupiah($row['net_total'] ?? $row['total']) }}</td>
                            <td class="num">{{ \App\Support\Format::rupiah($row['laba_kotor'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="ld-print__note">Qty dan total sudah dikurangi retur pada periode ini.</p>
        </section>
    @endif

    @if ($report['expenseByCategory'])
        <section>
            <h2>Pengeluaran per Kategori</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th class="num">Transaksi</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['expenseByCategory'] as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['nama'] }}{{ !empty($row['is_bahan_baku']) ? ' (Bahan Baku)' : '' }}</td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="num fw-medium">{{ \App\Support\Format::rupiah($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @php $journal = $report['cashJournal']; @endphp
    @if (count($journal['entries']) > 0)
        <section>
            <h2>Jurnal Arus Kas Bersih</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="num">Masuk</th>
                        <th class="num">Keluar</th>
                        <th class="num">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="fw-medium">Saldo awal periode</td>
                        <td class="num fw-medium">{{ \App\Support\Format::rupiah($journal['saldoAwal']) }}</td>
                    </tr>
                    @foreach ($journal['entries'] as $entry)
                        <tr>
                            <td>{{ \App\Support\Format::tanggal($entry['tanggal']) }}</td>
                            <td>{{ $entry['kategori'] }}</td>
                            <td>{{ $entry['keterangan'] }}</td>
                            <td class="num profit">{{ $entry['masuk'] > 0 ? \App\Support\Format::rupiah($entry['masuk']) : '—' }}</td>
                            <td class="num loss">{{ $entry['keluar'] > 0 ? \App\Support\Format::rupiah($entry['keluar']) : '—' }}</td>
                            <td class="num fw-medium">{{ \App\Support\Format::rupiah($entry['saldo']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total mutasi &amp; saldo akhir</td>
                        <td class="num profit">{{ \App\Support\Format::rupiah($journal['totalMasuk']) }}</td>
                        <td class="num loss">{{ \App\Support\Format::rupiah($journal['totalKeluar']) }}</td>
                        <td class="num">{{ \App\Support\Format::rupiah($journal['saldoAkhir']) }}</td>
                    </tr>
                </tfoot>
            </table>
            <p class="ld-print__note">Kas masuk = penjualan + modal; kas keluar = pengeluaran + retur. Arus kas riil, bukan laba.</p>
        </section>
    @endif

    <div class="ld-print__foot">
        Dicetak {{ now()->format('d/m/Y H:i') }} · Bahan Baku tercermin via HPP saat terjual · Biaya operasional = pengeluaran non-bahan-baku
    </div>
</body>
</html>
