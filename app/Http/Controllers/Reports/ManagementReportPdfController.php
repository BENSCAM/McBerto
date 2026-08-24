<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ManagementReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ManagementReportPdfController extends Controller
{
    public function __invoke(Request $request, ManagementReportService $reports)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:day,month,year'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $period = $validated['period'] ?? 'day';
        $date = $validated['date'] ?? now()->toDateString();
        $report = $reports->build($period, $date);

        $filename = 'rapport-management-'.$period.'-'.$report['start']->format('Ymd').'.pdf';

        return Pdf::loadView('reports.pdf.management', [
            'report' => $report,
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
        ])
            ->setPaper('a4')
            ->download($filename);
    }
}
