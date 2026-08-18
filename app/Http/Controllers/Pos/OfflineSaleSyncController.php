<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OfflineSaleSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales' => ['required', 'array', 'max:50'],
            'sales.*.offline_uuid' => ['required', 'string', 'max:80'],
            'sales.*.offline_reference' => ['nullable', 'string', 'max:80'],
            'sales.*.created_at' => ['required', 'date'],
            'sales.*.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'sales.*.service_area' => ['required', Rule::enum(ServiceArea::class)],
            'sales.*.total_amount' => ['required', 'integer', 'min:0'],
            'sales.*.amount_given' => ['nullable', 'integer', 'min:0'],
            'sales.*.change_due' => ['nullable', 'integer'],
            'sales.*.items' => ['required', 'array', 'min:1'],
            'sales.*.items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'sales.*.items.*.product_name' => ['required', 'string', 'max:255'],
            'sales.*.items.*.unit_price' => ['required', 'integer', 'min:0'],
            'sales.*.items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'sales.*.items.*.subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $synced = [];
        $failed = [];
        $warnings = [];
        $hasOfflineReferenceColumn = Schema::hasColumn('sales', 'offline_reference');

        foreach ($validated['sales'] as $payload) {
            if ($sale = Sale::where('offline_uuid', $payload['offline_uuid'])->first()) {
                $synced[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'sale_id' => $sale->id,
                    'receipt_number' => $sale->receipt_number,
                ];

                continue;
            }

            $createdAt = Carbon::parse($payload['created_at'])->timezone(config('app.timezone'));

            if ($createdAt->copy()->startOfDay()->gt(now()->startOfDay())) {
                $failed[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'message' => 'Impossible de synchroniser une vente datée dans le futur.',
                ];

                continue;
            }

            if (
                ! Auth::user()->isAtLeastManager()
                && ! $createdAt->isSameDay(now())
                && ! Auth::user()->canRecordSalesForDate($createdAt)
            ) {
                $failed[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'message' => 'Vous n’avez pas l’autorisation d’enregistrer une vente pour cette date.',
                ];

                continue;
            }

            if (CashRegisterClosing::whereDate('closing_date', $createdAt)->exists()) {
                $failed[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'message' => 'La caisse est déjà clôturée pour cette date.',
                ];

                continue;
            }

            $computedTotal = collect($payload['items'])->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);

            if ($computedTotal !== (int) $payload['total_amount']) {
                $failed[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'message' => 'Le total de la vente hors ligne est incohérent.',
                ];

                continue;
            }

            if (
                $payload['payment_method'] === PaymentMethod::Cash->value
                && $payload['amount_given'] !== null
                && (int) $payload['amount_given'] < (int) $payload['total_amount']
            ) {
                $failed[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'message' => 'Le montant reçu en espèces est insuffisant.',
                ];

                continue;
            }

            $priceWarnings = $this->priceWarnings($payload);

            $sale = DB::transaction(function () use ($payload, $createdAt, $hasOfflineReferenceColumn) {
                $saleData = [
                    'receipt_number' => Sale::nextReceiptNumber($createdAt),
                    'offline_uuid' => $payload['offline_uuid'],
                    'user_id' => Auth::id(),
                    'payment_method' => PaymentMethod::from($payload['payment_method']),
                    'service_area' => ServiceArea::from($payload['service_area']),
                    'sale_status' => SaleStatus::Completed,
                    'total_amount' => (int) $payload['total_amount'],
                    'amount_given' => $payload['amount_given'],
                    'change_due' => $payload['change_due'],
                ];

                if ($hasOfflineReferenceColumn) {
                    $saleData['offline_reference'] = $payload['offline_reference'] ?? null;
                }

                $sale = new Sale($saleData);
                $sale->created_at = $createdAt;
                $sale->updated_at = now();
                $sale->save();

                foreach ($payload['items'] as $item) {
                    $product = Product::find($item['product_id']);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product?->id,
                        'product_name' => $item['product_name'],
                        'unit_price' => (int) $item['unit_price'],
                        'quantity' => (int) $item['quantity'],
                        'subtotal' => (int) $item['subtotal'],
                    ]);
                }

                return $sale;
            });

            $synced[] = [
                'offline_uuid' => $payload['offline_uuid'],
                'sale_id' => $sale->id,
                'receipt_number' => $sale->receipt_number,
                'offline_reference' => $hasOfflineReferenceColumn ? $sale->offline_reference : ($payload['offline_reference'] ?? null),
                'warnings' => $priceWarnings,
            ];

            foreach ($priceWarnings as $warning) {
                $warnings[] = [
                    'offline_uuid' => $payload['offline_uuid'],
                    'message' => $warning,
                ];
            }
        }

        $this->logSyncSummary($synced, $failed, $warnings);

        return response()->json([
            'synced' => $synced,
            'failed' => $failed,
            'warnings' => $warnings,
            'catalog' => $this->offlineCatalog(),
        ]);
    }

    private function priceWarnings(array $payload): array
    {
        $productIds = collect($payload['items'])->pluck('product_id')->all();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return collect($payload['items'])
            ->map(function (array $item) use ($products) {
                $product = $products->get($item['product_id']);

                if (! $product || (int) $product->price === (int) $item['unit_price']) {
                    return null;
                }

                return "Prix offline conservé pour {$item['product_name']} : {$item['unit_price']} FCFA au lieu de {$product->price} FCFA serveur.";
            })
            ->filter()
            ->values()
            ->all();
    }

    private function logSyncSummary(array $synced, array $failed, array $warnings): void
    {
        if (empty($synced) && empty($failed) && empty($warnings)) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => empty($failed) ? 'offline_sync' : 'offline_sync_partial',
            'description' => count($synced).' vente(s) offline synchronisée(s), '.count($failed).' refusée(s)',
            'new_values' => [
                'synced' => $synced,
                'failed' => $failed,
                'warnings' => $warnings,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private function offlineCatalog(): array
    {
        $categories = Category::where('is_active', true)
            ->with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return [
            'version' => now()->timestamp,
            'serviceAreas' => collect(ServiceArea::cases())
                ->map(fn (ServiceArea $area) => ['value' => $area->value, 'label' => $area->label()])
                ->values()
                ->all(),
            'paymentMethods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method) => ['value' => $method->value, 'label' => $method->label()])
                ->values()
                ->all(),
            'categories' => $categories
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products' => $category->products
                        ->map(fn (Product $product) => [
                            'id' => $product->id,
                            'name' => $product->name,
                            'emoji' => $product->emoji,
                            'price' => $product->price,
                            'service_area' => $product->service_area->value,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }
}
