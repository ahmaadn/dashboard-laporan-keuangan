<?php

namespace App\Http\Controllers;

use App\Models\HppAdjustment;
use App\Services\PeriodResolver;
use App\Services\ReportService;
use App\Support\AppTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly PeriodResolver $periods,
    ) {}

    public function index(Request $request)
    {
        $period = (string) $request->query('period', 'bulan_ini');
        $start = $request->query('start');
        $end = $request->query('end');

        $filterError = $this->validatePeriodFilter($period, $start, $end);

        // Rentang tidak valid: jangan tampilkan data sama sekali, hanya pesan validasi.
        $report = $filterError === null
            ? $this->reportService->summary($period, $start, $end)
            : null;

        return view('reports.index', [
            'report' => $report,
            'periodOptions' => PeriodResolver::OPTIONS,
            'filterError' => $filterError,
            'tanggalHariIni' => AppTimezone::todayDateString(),
            'tanggalMulaiUsaha' => AppTimezone::TANGGAL_MULAI_USAHA,
            'maxRentangHari' => PeriodResolver::MAX_RANGE_DAYS,
        ]);
    }

    /**
     * Validasi rentang tanggal laporan; mengembalikan pesan kesalahan atau null.
     */
    private function validatePeriodFilter(string $period, ?string $start, ?string $end): ?string
    {
        $today = AppTimezone::todayDateString();

        if (! array_key_exists($period, PeriodResolver::OPTIONS)) {
            return 'Periode yang dipilih tidak valid.';
        }

        $validator = Validator::make(
            ['start' => $start, 'end' => $end],
            [
                'start' => ['nullable', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.$today],
                'end' => ['nullable', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.$today],
            ],
            [
                'start.date' => 'Tanggal awal tidak valid.',
                'start.after_or_equal' => 'Tanggal awal tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.'.',
                'start.before_or_equal' => 'Tanggal awal tidak boleh melebihi hari ini.',
                'end.date' => 'Tanggal akhir tidak valid.',
                'end.after_or_equal' => 'Tanggal akhir tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.'.',
                'end.before_or_equal' => 'Tanggal akhir tidak boleh melebihi hari ini.',
            ],
        );

        if ($validator->fails()) {
            return (string) $validator->errors()->first();
        }

        if (! $start || ! $end) {
            return null;
        }

        $tz = AppTimezone::name();
        $startDate = CarbonImmutable::parse($start, $tz)->startOfDay();
        $endDate = CarbonImmutable::parse($end, $tz)->startOfDay();

        if ($startDate->greaterThan($endDate)) {
            return 'Tanggal awal tidak boleh melebihi tanggal akhir.';
        }

        $days = $startDate->diffInDays($endDate) + 1;
        if ($days > PeriodResolver::MAX_RANGE_DAYS) {
            return 'Rentang periode maksimal '.PeriodResolver::MAX_RANGE_DAYS.' hari; rentang dipilih '.$days.' hari.';
        }

        return null;
    }

    public function storeHppAdjustment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
            'nominal' => ['required', 'numeric'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.' (usaha mulai beroperasi 2018).',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
            'nominal.required' => 'Nominal wajib diisi.',
        ]);

        $adj = HppAdjustment::create([
            'user_id' => $request->user()->id,
            'tanggal' => $validated['tanggal'],
            'nominal' => $validated['nominal'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'adjustment' => [
                'id' => $adj->id,
                'tanggal' => $adj->tanggal?->format('Y-m-d'),
                'nominal' => (int) $adj->nominal,
                'keterangan' => $adj->keterangan,
            ],
        ], 201);
    }

    public function destroyHppAdjustment(HppAdjustment $hppAdjustment): JsonResponse
    {
        $hppAdjustment->delete();

        return response()->json(['success' => true]);
    }
}
