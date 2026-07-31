<?php

namespace App\Http\Controllers;

use App\Models\HppAdjustment;
use App\Services\PeriodResolver;
use App\Services\ReportService;
use App\Support\AppTimezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly PeriodResolver $periods,
    ) {}

    public function index(Request $request)
    {
        $period = $request->query('period', 'bulan_ini');
        $report = $this->reportService->summary($period, $request->query('start'), $request->query('end'));

        return view('reports.index', [
            'report' => $report,
            'periodOptions' => PeriodResolver::OPTIONS,
        ]);
    }

    public function storeHppAdjustment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'before_or_equal:'.AppTimezone::todayDateString()],
            'nominal' => ['required', 'numeric'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'tanggal.required' => 'Tanggal wajib diisi.',
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
