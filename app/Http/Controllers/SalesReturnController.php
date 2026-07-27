<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Http\Resources\UserResource;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request)
    {
        $returns = SalesReturn::with(['income', 'product', 'user'])
            ->orderByDesc('tanggal')
            ->get();

        return view('sales_returns.index', [
            'retur' => SalesReturnResource::collection($returns)->resolve(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(SalesReturnRequest $request): JsonResponse
    {
        $income = $request->ensureJumlahWithinLimit();

        $entry = DB::transaction(function () use ($request, $income) {
            $product = $income->product_id ? Product::lockForUpdate()->find($income->product_id) : null;
            $hargaSatuan = (float) $income->harga_satuan;
            $nominalRetur = $hargaSatuan * (int) $request->input('jumlah');

            $entry = SalesReturn::create([
                'income_id' => $income->id,
                'product_id' => $income->product_id,
                'user_id' => $request->user()->id,
                'tanggal' => $request->input('tanggal'),
                'jumlah' => (int) $request->input('jumlah'),
                'nominal_retur' => $nominalRetur,
                'alasan' => $request->input('alasan'),
            ]);

            if ($product) {
                $this->stock->catatMasuk(
                    $product,
                    (int) $request->input('jumlah'),
                    'retur',
                    $entry->id,
                    'Retur penjualan #'.$income->id,
                    $request->user()->id,
                    $request->input('tanggal'),
                );
            }

            return $entry;
        });

        return response()->json([
            'success' => true,
            'resource' => SalesReturnResource::make($entry)->resolve(),
        ], 201);
    }

    public function destroy(Request $request, SalesReturn $salesReturn): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat menghapus retur.');
        }

        // Catatan: stok TIDAK dikembalikan saat soft delete retur — ledger stok
        // bersifat append-only. Untuk "membatalkan" retur, hapus transaksi
        // penjualan asalnya.
        $salesReturn->delete();

        return response()->json(['success' => true]);
    }
}
