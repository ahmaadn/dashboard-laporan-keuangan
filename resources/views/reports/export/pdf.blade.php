<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Keuangan — {{ config('app.name', 'BM Leather') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; margin: 32px; }
        .ld-print__head { margin-bottom: 18px; }
        .ld-print__brand { font-size: 16px; font-weight: 600; color: #111827; }
        .ld-print__title { font-size: 19px; font-weight: 600; margin-top: 10px; }
        .ld-print__meta { color: #6b7280; margin-top: 4px; font-size: 11px; }
        .ld-print__summary { width: 100%; margin-top: 18px; border-collapse: collapse; }
        .ld-print__summary td { border: 1px solid #e5e7eb; padding: 10px 12px; width: 33.33%; }
        .ld-print__summary .label { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        .ld-print__summary .value { font-size: 17px; font-weight: 600; margin-top: 4px; }
        .ld-print__summary .profit { color: #047857; }
        .ld-print__summary .loss { color: #b91c1c; }
        section { margin-top: 20px; }
        h2 { font-size: 13px; margin-bottom: 6px; color: #374151; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { text-align: left; padding: 7px 9px; border-bottom: 1px solid #e5e7eb; }
        thead th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #4b5563; }
        .num { text-align: right; }
        .fw-medium { font-weight: 500; }
        .ld-print__foot { margin-top: 24px; color: #9ca3af; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="ld-print__head">
        <div class="ld-print__brand">{{ config('app.name', 'BM Leather') }}</div>
        <div class="ld-print__title">Laporan Keuangan</div>
        <div class="ld-print__meta">Periode: {{ $report['rangeLabel'] }}</div>
    </div>

    <table class="ld-print__summary">
        <tr>
            <td>
                <div class="label">Total Pemasukan</div>
                <div class="value">{{ \App\Support\Format::rupiah($report['totalIncome']) }}</div>
            </td>
            <td>
                <div class="label">Total Pengeluaran</div>
                <div class="value">{{ \App\Support\Format::rupiah($report['totalExpense']) }}</div>
            </td>
            <td>
                <div class="label">Laba / Rugi</div>
                <div class="value {{ $report['profit'] >= 0 ? 'profit' : 'loss' }}">{{ \App\Support\Format::rupiah($report['profit']) }}</div>
            </td>
        </tr>
    </table>

    @if ($report['incomeByProduct'])
        <section>
            <h2>Pemasukan per Produk</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="num">Jumlah Terjual</th>
                        <th class="num">Transaksi</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['incomeByProduct'] as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['nama'] }}</td>
                            <td class="num">{{ $row['qty'] }}</td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="num fw-medium">{{ \App\Support\Format::rupiah($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
                            <td class="fw-medium">{{ $row['nama'] }}</td>
                            <td class="num">{{ $row['count'] }}</td>
                            <td class="num fw-medium">{{ \App\Support\Format::rupiah($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if (! $report['hasData'])
        <section>
            <p style="color:#6b7280">Belum ada transaksi pada periode ini.</p>
        </section>
    @endif

    <div class="ld-print__foot">
        Dicetak dari {{ config('app.name', 'BM Leather') }} — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
