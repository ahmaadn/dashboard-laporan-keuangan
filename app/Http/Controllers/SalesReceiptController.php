<?php

namespace App\Http\Controllers;

use App\Services\SalesReceiptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Nota penjualan sebagai bukti transaksi. Satu nota mewakili seluruh baris
 * `incomes` yang berbagi `nomor_transaksi` yang sama (satu transaksi kasir).
 */
class SalesReceiptController extends Controller
{
    public function __construct(private readonly SalesReceiptService $receipts) {}

    /** Data nota untuk preview modal di halaman Pemasukan. */
    public function show(string $nomorTransaksi): JsonResponse
    {
        $receipt = $this->receipts->byNomorTransaksi($nomorTransaksi);

        if ($receipt === null) {
            return response()->json([
                'success' => false,
                'message' => 'Nota tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'nota' => $receipt,
        ]);
    }

    /** Unduh nota sebagai PDF. */
    public function pdf(string $nomorTransaksi)
    {
        $receipt = $this->receipts->byNomorTransaksi($nomorTransaksi);

        abort_if($receipt === null, 404, 'Nota tidak ditemukan.');

        $filename = Str::slug('nota-'.$receipt['nomor_transaksi']).'.pdf';

        return Pdf::loadView('income.nota', ['nota' => $receipt])
            ->setPaper('a5', 'portrait')
            ->download($filename);
    }
}
