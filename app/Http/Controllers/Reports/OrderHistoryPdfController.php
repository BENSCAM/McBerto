<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderHistoryPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $startInput = $validated['start_date'] ?? now()->toDateString();
        $endInput = $validated['end_date'] ?? $startInput;
        $start = Carbon::createFromFormat('Y-m-d', $startInput)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endInput)->endOfDay();

        abort_if($end->lt($start), 422, 'La date de fin doit être supérieure ou égale à la date de début.');

        $sales = Sale::completed()
            ->with(['items' => fn ($query) => $query->orderBy('id'), 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $filename = $start->isSameDay($end)
            ? 'historique-commandes-'.$start->format('Ymd').'.pdf'
            : 'historique-commandes-'.$start->format('Ymd').'-'.$end->format('Ymd').'.pdf';

        abort_unless(class_exists(Dompdf::class), 500, 'La librairie PDF Dompdf n’est pas installée sur le serveur.');

        $html = view('reports.pdf.order-history', [
            'sales' => $sales,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
            'generatedBy' => $request->user(),
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('a4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
