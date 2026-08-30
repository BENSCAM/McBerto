<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Historique des commandes</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        h1 { color: #dc2626; font-size: 22px; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; color: #374151; font-weight: 700; text-align: left; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px 7px; vertical-align: top; }
        .header { border-bottom: 3px solid #dc2626; margin-bottom: 14px; padding-bottom: 12px; }
        .meta, .muted { color: #6b7280; }
        .summary { margin: 12px 0 16px; }
        .summary td { border: 1px solid #e5e7eb; padding: 10px; width: 33.33%; }
        .value { color: #111827; font-size: 16px; font-weight: 700; margin-top: 3px; }
        .amount { text-align: right; white-space: nowrap; }
        .ticket { page-break-inside: avoid; margin-bottom: 12px; }
        .ticket-title { background: #f9fafb; border: 1px solid #e5e7eb; border-bottom: 0; padding: 8px; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    @php
        $money = fn ($amount) => number_format((int) $amount, 0, ',', ' ').' FCFA';
        $period = $start->isSameDay($end)
            ? $start->format('d/m/Y')
            : $start->format('d/m/Y').' au '.$end->format('d/m/Y');
        $total = $sales->sum('total_amount');
        $itemsCount = $sales->sum(fn ($sale) => $sale->items->sum('quantity'));
    @endphp

    <div class="header">
        <h1>Historique des commandes McBerto</h1>
        <p class="meta">Période : {{ $period }} | Généré le {{ $generatedAt->format('d/m/Y H:i') }} par {{ $generatedBy->name }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="muted">Commandes</div>
                <div class="value">{{ $sales->count() }}</div>
            </td>
            <td>
                <div class="muted">Articles vendus</div>
                <div class="value">{{ $itemsCount }}</div>
            </td>
            <td>
                <div class="muted">Total encaissé</div>
                <div class="value">{{ $money($total) }}</div>
            </td>
        </tr>
    </table>

    <h2>Commandes détaillées</h2>

    @forelse ($sales as $sale)
        <div class="ticket">
            <div class="ticket-title">
                <strong>{{ $sale->receipt_number ?? '#'.$sale->id }}</strong>
                <span class="muted">
                    - {{ $sale->created_at->format('d/m/Y H:i') }}
                    - {{ $sale->user?->name ?? 'Utilisateur supprimé' }}
                    - {{ $sale->service_area->label() }}
                    - {{ $sale->payment_method->label() }}
                </span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="amount">Prix unitaire</th>
                        <th class="amount">Quantité</th>
                        <th class="amount">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="amount">{{ $money($item->unit_price) }}</td>
                            <td class="amount">{{ $item->quantity }}</td>
                            <td class="amount">{{ $money($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="amount"><strong>Total</strong></td>
                        <td class="amount"><strong>{{ $money($sale->total_amount) }}</strong></td>
                    </tr>
                    @if ($sale->amount_given !== null)
                        <tr>
                            <td colspan="3" class="amount muted">Montant donné</td>
                            <td class="amount muted">{{ $money($sale->amount_given) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="amount muted">Monnaie rendue</td>
                            <td class="amount muted">{{ $money($sale->change_due) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @empty
        <p class="muted">Aucune commande enregistrée sur cette période.</p>
    @endforelse
</body>
</html>
