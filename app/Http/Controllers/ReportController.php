<?php

namespace App\Http\Controllers;

use App\Services\PeriodResolver;
use App\Services\ReportService;
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
}
