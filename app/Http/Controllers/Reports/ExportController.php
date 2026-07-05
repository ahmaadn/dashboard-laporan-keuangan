<?php

namespace App\Http\Controllers\Reports;

use App\Services\Reports\ExportRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Handles PDF (server-rendered via dompdf) and Excel exports for the
 * financial report. Both exports reuse {@see ExportRenderer::report()} so they
 * stay consistent with the on-screen report for the selected period.
 */
final class ExportController extends Controller
{
    public function __construct(private readonly ExportRenderer $renderer) {}

    public function pdf(Request $request)
    {
        $report = $this->renderer->report(
            $request->query('period', 'bulan_ini'),
            $request->query('start'),
            $request->query('end'),
        );

        $filename = Str::slug('laporan-keuangan-'.$report['start'].'-'.$report['end']).'.pdf';

        return Pdf::loadView('reports.export.pdf', ['report' => $report])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function excel(Request $request)
    {
        $report = $this->renderer->report(
            $request->query('period', 'bulan_ini'),
            $request->query('start'),
            $request->query('end'),
        );

        $filename = Str::slug('laporan-keuangan-'.$report['start'].'-'.$report['end']).'.xls';

        return response($this->renderer->excel($report), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
