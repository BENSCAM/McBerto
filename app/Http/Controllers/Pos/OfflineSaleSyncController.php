<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Http\Controllers\Controller;
use App\Models\CashRegisterClosing;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfflineSaleSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sales' => ['required', 'array', 'max:50'],
            'sales.*.offline_uuid' => ['required', 'string', 'max:80'],
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

            $sale = DB::transaction(function () use ($payload, $createdAt) {
                $sale = new Sale([
                    'receipt_number' => Sale::nextReceiptNumber($createdAt),
                    'offline_uuid' => $payload['offline_uuid'],
                    'user_id' => Auth::id(),
                    'payment_method' => PaymentMethod::from($payload['payment_method']),
                    'service_area' => ServiceArea::from($payload['service_area']),
                    'sale_status' => SaleStatus::Completed,
                    'total_amount' => (int) $payload['total_amount'],
                    'amount_given' => $payload['amount_given'],
                    'change_due' => $payload['change_due'],
                ]);
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
            ];
        }

        return response()->json([
            'synced' => $synced,
            'failed' => $failed,
        ]);
    }
}
