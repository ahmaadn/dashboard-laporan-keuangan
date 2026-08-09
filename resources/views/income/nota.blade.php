<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Nota {{ $nota['nomor_transaksi'] }} — {{ $nota['usaha']['nama'] }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; margin: 28px; }
        .nota__head { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .nota__brand { font-size: 17px; font-weight: 700; color: #111827; letter-spacing: .5px; }
        .nota__subtitle { font-size: 11px; color: #6b7280; margin-top: 3px; }
        .nota__title { font-size: 13px; font-weight: 600; margin-top: 8px; text-transform: uppercase; letter-spacing: .08em; }
        .nota__meta { width: 100%; margin-top: 14px; border-collapse: collapse; font-size: 11px; }
        .nota__meta td { padding: 3px 0; vertical-align: top; }
        .nota__meta .k { color: #6b7280; width: 38%; }
        .nota__meta .v { font-weight: 600; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.items th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #4b5563; border-bottom: 1px solid #111827; padding: 6px 4px; }
        table.items td { padding: 6px 4px; border-bottom: 1px dashed #d1d5db; }
        .num { text-align: right; }
        .totals { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .totals td { padding: 4px 4px; }
        .totals .label { color: #4b5563; }
        .totals .value { text-align: right; font-weight: 600; }
        .totals .grand td { border-top: 2px solid #111827; font-size: 14px; font-weight: 700; padding-top: 7px; }
        .retur { color: #b91c1c; }
        .nota__foot { margin-top: 22px; text-align: center; color: #6b7280; font-size: 10px; border-top: 1px dashed #d1d5db; padding-top: 8px; }
        .badge { font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="nota__head">
        <div class="nota__brand">{{ $nota['usaha']['nama'] }}</div>
        <div class="nota__subtitle">Kerajinan Kulit</div>
        <div class="nota__title">Nota Penjualan</div>
    </div>

    <table class="nota__meta">
        <tr>
            <td class="k">No. Transaksi</td>
            <td class="v">{{ $nota['nomor_transaksi'] }}</td>
        </tr>
        <tr>
            <td class="k">Tanggal</td>
            <td class="v">{{ $nota['tanggal_label'] }}</td>
        </tr>
        <tr>
            <td class="k">Jenis</td>
            <td class="v">{{ $nota['jenis_transaksi_label'] }}</td>
        </tr>
        <tr>
            <td class="k">Kasir</td>
            <td class="v">{{ $nota['kasir'] }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($nota['items'] as $item)
                <tr>
                    <td>
                        {{ $item['nama_produk'] }}
                        @if ($item['harga_tipe'] === 'grosir')
                            <span class="badge">(grosir)</span>
                        @endif
                        @if ($item['jumlah_diretur'] > 0)
                            <br><span class="badge retur">Diretur {{ $item['jumlah_diretur'] }} pcs</span>
                        @endif
                    </td>
                    <td class="num">{{ $item['jumlah'] }}</td>
                    <td class="num">{{ \App\Support\Format::rupiah($item['harga_satuan']) }}</td>
                    <td class="num">{{ \App\Support\Format::rupiah($item['subtotal']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total Item</td>
            <td class="value">{{ $nota['total_qty'] }} pcs</td>
        </tr>
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ \App\Support\Format::rupiah($nota['subtotal']) }}</td>
        </tr>
        @if ($nota['total_retur'] > 0)
            <tr>
                <td class="label retur">Retur</td>
                <td class="value retur">− {{ \App\Support\Format::rupiah($nota['total_retur']) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>TOTAL</td>
            <td class="value">{{ \App\Support\Format::rupiah($nota['total']) }}</td>
        </tr>
    </table>

    @if ($nota['keterangan'])
        <p style="margin-top: 12px; font-size: 11px; color: #4b5563;">
            Keterangan: {{ $nota['keterangan'] }}
        </p>
    @endif

    <div class="nota__foot">
        Terima kasih atas pembelian Anda.<br>
        Simpan nota ini sebagai bukti transaksi untuk keperluan retur atau komplain.<br>
        Dicetak {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
