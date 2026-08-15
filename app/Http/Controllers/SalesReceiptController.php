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

        $receipt['usaha']['logo_data_uri'] = $this->logoDataUri();

        return Pdf::loadView('income.nota', ['nota' => $receipt])
            ->setPaper([0, 0, 249.4488, 500 + (count($receipt['items']) * 72)])
            ->download($filename);
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('logo-t.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
