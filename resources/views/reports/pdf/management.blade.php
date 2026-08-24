<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport management</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.35; }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: 22px; color: #dc2626; }
        h2 { font-size: 15px; margin: 22px 0 8px; }
        h3 { font-size: 12px; color: #4b5563; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; color: #374151; font-weight: 700; text-align: left; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px 7px; vertical-align: top; }
        .header { border-bottom: 3px solid #dc2626; padding-bottom: 12px; margin-bottom: 16px; }
        .meta { color: #6b7280; margin-top: 4px; }
        .summary { width: 100%; margin-top: 12px; }
        .summary td { border: 1px solid #e5e7eb; padding: 10px; width: 25%; }
        .value { font-size: 16px; font-weight: 700; color: #111827; }
        .amount { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; }
        .danger { color: #dc2626; }
        .ok { color: #047857; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; background: #f3f4f6; }
        .page-break { page-break-before: always; }
        .small { font-size: 10px; }
        .items { color: #374151; }
    </style>
</head>
<body>
    @php
        $money = fn ($amount) => number_format((int) $amount, 0, ',', ' ').' FCFA';
        $dateTime = fn ($date) => $date ? $date->format('d/m/Y H:i') : '-';
        $date = fn ($date) => $date ? $date->format('d/m/Y') : '-';
    @endphp

    <div class="header">
        <h1>Rapport management McBerto</h1>
        <p class="meta">Période : {{ $report['label'] }} | Généré le {{ $generatedAt->format('d/m/Y H:i') }} par {{ $generatedBy->name }}</p>
    </div>

    <table class="summary">
        <tr>
            <td>
                <h3>Chiffre d'affaires</h3>
                <div class="value">{{ $money($report['summary']['revenue']) }}</div>
            </td>
            <td>
                <h3>Commandes validées</h3>
                <div class="value">{{ $report['summary']['orders_count'] }}</div>
            </td>
            <td>
                <h3>Ticket moyen</h3>
                <div class="value">{{ $money($report['summary']['average_ticket']) }}</div>
            </td>
            <td>
                <h3>Bénéfice net</h3>
                <div class="value {{ $report['summary']['net_profit'] >= 0 ? 'ok' : 'danger' }}">{{ $money($report['summary']['net_profit']) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <h3>Dépenses</h3>
                <div class="value">{{ $money($report['summary']['expenses']) }}</div>
            </td>
            <td>
                <h3>Commandes annulées</h3>
                <div class="value danger">{{ $report['summary']['canceled_orders_count'] }}</div>
            </td>
            <td>
                <h3>Montant annulé</h3>
                <div class="value danger">{{ $money($report['summary']['canceled_orders_total']) }}</div>
            </td>
            <td>
                <h3>Tickets listés</h3>
                <div class="value">{{ min($report['sales_total_count'], 120) }} / {{ $report['sales_total_count'] }}</div>
            </td>
        </tr>
    </table>

    <h2>Répartition par zone</h2>
    <table>
        <thead>
            <tr>
                <th>Zone</th>
                <th class="amount">Commandes</th>
                <th class="amount">Chiffre d'affaires</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['service_areas'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="amount">{{ $row['orders_count'] }}</td>
                    <td class="amount">{{ $money($row['revenue']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Moyens de paiement</h2>
    <table>
        <thead>
            <tr>
                <th>Moyen</th>
                <th class="amount">Commandes</th>
                <th class="amount">Montant</th>
                <th class="amount">Part</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['payment_methods'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="amount">{{ $row['orders_count'] }}</td>
                    <td class="amount">{{ $money($row['amount']) }}</td>
                    <td class="amount">{{ number_format($row['percent'], 1, ',', ' ') }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top produits</h2>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Catégorie</th>
                <th>Zone</th>
                <th class="amount">Quantité</th>
                <th class="amount">CA</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['top_products'] as $product)
                <tr>
                    <td>{{ $product->product_name }}</td>
                    <td>{{ $product->category_name }}</td>
                    <td>{{ \App\Enums\ServiceArea::tryFrom($product->service_area)?->label() ?? $product->service_area }}</td>
                    <td class="amount">{{ (int) $product->total_quantity }}</td>
                    <td class="amount">{{ $money($product->total_revenue) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Aucun produit vendu sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>Ventes détaillées</h2>
    <table>
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Date</th>
                <th>Caissier</th>
                <th>Zone</th>
                <th>Paiement</th>
                <th>Contenu</th>
                <th class="amount">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['sales'] as $sale)
                <tr>
                    <td>
                        {{ $sale->receipt_number }}
                        @if ($sale->sale_status === \App\Enums\SaleStatus::Canceled)
                            <br><span class="danger small">Annulée</span>
                        @endif
                    </td>
                    <td>{{ $dateTime($sale->created_at) }}</td>
                    <td>{{ $sale->user?->name ?? '-' }}</td>
                    <td>{{ $sale->service_area->label() }}</td>
                    <td>{{ $sale->payment_method->label() }}</td>
                    <td class="items">
                        @foreach ($sale->items as $item)
                            {{ $item->product_name }} x{{ $item->quantity }}@if (! $loop->last), @endif
                        @endforeach
                    </td>
                    <td class="amount">{{ $money($sale->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Aucune vente sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($report['sales_total_count'] > 120)
        <p class="muted small">Le PDF affiche les 120 ventes les plus récentes sur {{ $report['sales_total_count'] }} ventes.</p>
    @endif

    <h2>Annulations avec justificatifs</h2>
    <table>
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Annulée le</th>
                <th>Caissier</th>
                <th>Annulée par</th>
                <th>Justificatif</th>
                <th class="amount">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['canceled_sales'] as $sale)
                <tr>
                    <td>{{ $sale->receipt_number }}</td>
                    <td>{{ $dateTime($sale->canceled_at) }}</td>
                    <td>{{ $sale->user?->name ?? '-' }}</td>
                    <td>{{ $sale->canceledBy?->name ?? '-' }}</td>
                    <td>{{ $sale->cancellation_reason ?: '-' }}</td>
                    <td class="amount danger">{{ $money($sale->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Aucune annulation sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Clôtures de caisse</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Clôturée par</th>
                <th class="amount">Total attendu</th>
                <th class="amount">Espèces comptées</th>
                <th class="amount">Écart</th>
                <th class="amount">Commandes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['closings'] as $closing)
                <tr>
                    <td>{{ $date($closing->closing_date) }}</td>
                    <td>{{ $closing->closedBy?->name ?? '-' }}</td>
                    <td class="amount">{{ $money($closing->total_amount) }}</td>
                    <td class="amount">{{ $money($closing->counted_cash) }}</td>
                    <td class="amount {{ $closing->variance === 0 ? '' : 'danger' }}">{{ $money($closing->variance) }}</td>
                    <td class="amount">{{ $closing->total_orders_count }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Aucune clôture sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Dépenses</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Utilisateur</th>
                <th class="amount">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['expenses'] as $expense)
                <tr>
                    <td>{{ $date($expense->expense_date) }}</td>
                    <td>{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? $expense->category }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ $expense->user?->name ?? '-' }}</td>
                    <td class="amount">{{ $money($expense->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Aucune dépense sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
