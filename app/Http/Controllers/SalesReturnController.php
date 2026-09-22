<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Http\Resources\UserResource;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Services\StockService;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Cari baris penjualan (income) yang masih memiliki sisa retur,
     * untuk dipilih sebagai penjualan asal pada form retur.
     */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:64'],
            'income_id' => ['nullable', 'integer'],
        ]);

        $q = trim((string) ($data['q'] ?? ''));
        $pinnedIncomeId = (int) ($data['income_id'] ?? 0);

        $incomes = Income::query()
            ->select('incomes.*')
            ->selectRaw('COALESCE(SUM(sales_returns.jumlah), 0) as jumlah_diretur')
            ->leftJoin('sales_returns', function (JoinClause $join) {
                $join->on('sales_returns.income_id', '=', 'incomes.id')
                    ->whereNull('sales_returns.deleted_at');
            })
            ->when($q !== '' || $pinnedIncomeId > 0, function ($query) use ($q, $pinnedIncomeId) {
                $query->where(function ($where) use ($q, $pinnedIncomeId) {
                    if ($q !== '') {
                        $where->where('incomes.nomor_transaksi', 'like', "%{$q}%")
                            ->orWhere('incomes.keterangan', 'like', "%{$q}%")
                            ->orWhereHas('product', fn ($p) => $p->where('nama', 'like', "%{$q}%"));

                        if (ctype_digit($q)) {
                            $where->orWhere('incomes.id', (int) $q);
                        }
                    }

                    if ($pinnedIncomeId > 0) {
                        $where->orWhere('incomes.id', $pinnedIncomeId);
                    }
                });
            })
            ->with('product')
            ->groupBy('incomes.id')
            ->havingRaw('incomes.jumlah > COALESCE(SUM(sales_returns.jumlah), 0)')
            ->orderByDesc('incomes.tanggal_transaksi')
            ->orderByDesc('incomes.id')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'options' => $incomes->map(fn (Income $income) => [
                'id' => $income->id,
                'nomor_transaksi' => $income->nomor_transaksi,
                'tanggal_transaksi' => $income->tanggal_transaksi?->format('Y-m-d'),
                'nama_produk' => $income->product?->nama,
                'jumlah' => (int) $income->jumlah,
                'sisa_retur' => max(0, (int) $income->jumlah - (int) $income->jumlah_diretur),
                'harga_satuan' => (int) $income->harga_satuan,
            ])->values()->all(),
        ]);
    }

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

            return $entry->load(['income', 'product', 'user']);
        });

        return response()->json([
            'success' => true,
            'resource' => SalesReturnResource::make($entry)->resolve(),
            'income' => [
                'id' => $income->id,
                'jumlah_diretur' => $income->fresh()->jumlahDiretur(),
                'sisa_retur' => $income->fresh()->sisaRetur(),
                'status' => $income->fresh()->statusTransaksi(),
                'status_label' => $income->fresh()->statusTransaksiLabel(),
            ],
        ], 201);
    }

    public function destroy(Request $request, SalesReturn $salesReturn): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat menghapus retur.');
        }

        DB::transaction(function () use ($request, $salesReturn) {
            if ($salesReturn->product_id) {
                $product = Product::lockForUpdate()->find($salesReturn->product_id);
                if ($product) {
                    $this->stock->catatKeluar(
                        $product,
                        (int) $salesReturn->jumlah,
                        'retur',
                        $salesReturn->id,
                        'Pembatalan retur #'.$salesReturn->id.' (stok dikurangi kembali)',
                        $request->user()->id,
                        $salesReturn->tanggal?->toDateString(),
                    );
                }
            }

            $salesReturn->delete();
        });

        return response()->json(['success' => true]);
    }
}
