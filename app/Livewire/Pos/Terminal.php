<?php

namespace App\Livewire\Pos;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ServiceArea;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Terminal extends Component
{
    public ?int $activeCategoryId = null;

    public string $activeServiceArea = 'standard';

    public string $search = '';

    /** @var array<int, array{name: string, emoji: ?string, price: int, quantity: int}> */
    public array $cart = [];

    public bool $showCheckout = false;

    /**
     * Which payment method the checkout modal is showing details for.
     * Null means the method-selection list is shown. 'cash' switches to
     * the amount-given / change-due sub-screen before the sale is finalized.
     */
    public ?string $checkoutMethod = null;

    public string $amountGiven = '';

    /** @var array{id: int, receipt_number: string, items: array, total: int, payment_method: PaymentMethod, service_area: ServiceArea, created_at: Carbon, cashier: string, amount_given: ?int, change_due: ?int}|null */
    public ?array $lastSaleReceipt = null;

    public ?int $salePendingCancellationId = null;

    public string $cancellationReason = '';

    public function mount(): void
    {
        $this->activeCategoryId = $this->categories()->first()?->id;
    }

    #[Computed]
    public function todayClosing(): ?CashRegisterClosing
    {
        return CashRegisterClosing::whereDate('closing_date', now())->with('closedBy')->first();
    }

    public function reopenRegister(): void
    {
        if (! Auth::user()->isAtLeastManager()) {
            return;
        }

        CashRegisterClosing::reopenToday();
        unset($this->todayClosing);
    }

    #[Computed]
    public function categoriesWithProducts()
    {
        return $this->categories();
    }

    protected function categories()
    {
        return Category::where('is_active', true)
            ->whereHas('products', fn ($q) => $q
                ->where('is_active', true)
                ->where('service_area', $this->activeServiceArea))
            ->with(['products' => fn ($q) => $q
                ->where('is_active', true)
                ->where('service_area', $this->activeServiceArea)
                ->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function serviceAreaOptions(): array
    {
        return ServiceArea::cases();
    }

    public function offlineCatalog(): array
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

    public function selectServiceArea(string $serviceArea): void
    {
        ServiceArea::from($serviceArea);

        if ($this->activeServiceArea === $serviceArea) {
            return;
        }

        $this->activeServiceArea = $serviceArea;
        $this->cart = [];
        $this->search = '';
        $this->activeCategoryId = $this->categories()->first()?->id;
    }

    public function selectCategory(int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
        $this->search = '';
    }

    public function updatedSearch(): void
    {
        // Searching browses across all categories; clear the active
        // category highlight so the sidebar doesn't show a stale selection.
        if (trim($this->search) !== '') {
            $this->activeCategoryId = null;
        } elseif ($this->activeCategoryId === null) {
            $this->activeCategoryId = $this->categories()->first()?->id;
        }
    }

    #[Computed]
    public function visibleProducts()
    {
        if (trim($this->search) !== '') {
            return Product::where('is_active', true)
                ->where('service_area', $this->activeServiceArea)
                ->where('name', 'like', '%'.$this->search.'%')
                ->orderBy('name')
                ->get();
        }

        return $this->categoriesWithProducts->firstWhere('id', $this->activeCategoryId)?->products ?? collect();
    }

    public function addToCart(int $productId): void
    {
        if ($this->todayClosing) {
            return;
        }

        $product = Product::where('service_area', $this->activeServiceArea)->findOrFail($productId);

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = [
                'name' => $product->name,
                'emoji' => $product->emoji,
                'price' => $product->price,
                'quantity' => 1,
            ];
        }
    }

    public function incrementQty(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        }
    }

    public function decrementQty(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantity']--;

        if ($this->cart[$productId]['quantity'] <= 0) {
            unset($this->cart[$productId]);
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function setQuantity(int $productId, $quantity): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $quantity = (int) $quantity;

        if ($quantity <= 0) {
            unset($this->cart[$productId]);

            return;
        }

        $this->cart[$productId]['quantity'] = min($quantity, 999);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    #[Computed]
    public function cartTotal(): int
    {
        return array_sum(array_map(
            fn ($item) => $item['price'] * $item['quantity'],
            $this->cart
        ));
    }

    #[Computed]
    public function recentSales()
    {
        return Sale::whereDate('created_at', now())
            ->with(['user', 'canceledBy'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    public function openCancelSale(int $saleId): void
    {
        if ($this->todayClosing) {
            return;
        }

        $sale = Sale::whereDate('created_at', now())->findOrFail($saleId);

        if ($sale->sale_status === SaleStatus::Canceled || $sale->cash_register_closing_id !== null) {
            return;
        }

        if (! Auth::user()->isAtLeastManager() && $sale->user_id !== Auth::id()) {
            return;
        }

        $this->resetErrorBag('cancellationReason');
        $this->salePendingCancellationId = $sale->id;
        $this->cancellationReason = '';
    }

    public function closeCancelSale(): void
    {
        $this->salePendingCancellationId = null;
        $this->cancellationReason = '';
        $this->resetErrorBag('cancellationReason');
    }

    public function confirmCancelSale(): void
    {
        if (! $this->salePendingCancellationId) {
            return;
        }

        $this->validate([
            'cancellationReason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'cancellationReason.required' => 'Le justificatif est obligatoire.',
            'cancellationReason.min' => 'Le justificatif doit contenir au moins 3 caractères.',
            'cancellationReason.max' => 'Le justificatif ne doit pas dépasser 255 caractères.',
        ]);

        $this->cancelSale($this->salePendingCancellationId, $this->cancellationReason);
    }

    public function cancelSale(int $saleId, string $reason): void
    {
        if ($this->todayClosing) {
            return;
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages([
                'cancellationReason' => 'Le justificatif doit contenir au moins 3 caractères.',
            ]);
        }

        $sale = Sale::whereDate('created_at', now())->findOrFail($saleId);

        if ($sale->sale_status === SaleStatus::Canceled || $sale->cash_register_closing_id !== null) {
            return;
        }

        if (! Auth::user()->isAtLeastManager() && $sale->user_id !== Auth::id()) {
            return;
        }

        $sale->update([
            'sale_status' => SaleStatus::Canceled,
            'canceled_by' => Auth::id(),
            'canceled_at' => now(),
            'cancellation_reason' => mb_strimwidth($reason, 0, 255),
        ]);

        $this->salePendingCancellationId = null;
        $this->cancellationReason = '';
        unset($this->recentSales);
    }

    #[On('offline-sales-synced')]
    public function refreshRecentSalesAfterOfflineSync(): void
    {
        unset($this->recentSales);
    }

    public function openCheckout(): void
    {
        if (empty($this->cart)) {
            return;
        }

        $this->showCheckout = true;
    }

    public function closeCheckout(): void
    {
        $this->showCheckout = false;
        $this->checkoutMethod = null;
        $this->amountGiven = '';
    }

    public function selectPaymentMethod(string $paymentMethod): void
    {
        if ($paymentMethod === PaymentMethod::Cash->value) {
            $this->checkoutMethod = 'cash';
            $this->amountGiven = '';

            return;
        }

        $this->completeSale($paymentMethod);
    }

    public function backToPaymentMethods(): void
    {
        $this->checkoutMethod = null;
        $this->amountGiven = '';
    }

    public function setAmountGiven(int $amount): void
    {
        $this->amountGiven = (string) $amount;
    }

    #[Computed]
    public function changeDue(): ?int
    {
        if ($this->amountGiven === '' || ! is_numeric($this->amountGiven)) {
            return null;
        }

        return (int) $this->amountGiven - $this->cartTotal();
    }

    public function confirmCashSale(): void
    {
        if ($this->changeDue() === null || $this->changeDue() < 0) {
            return;
        }

        $this->completeSale('cash', (int) $this->amountGiven, $this->changeDue());
    }

    public function completeSale(string $paymentMethod, ?int $amountGiven = null, ?int $changeDue = null): void
    {
        if (empty($this->cart) || $this->todayClosing) {
            return;
        }

        $method = PaymentMethod::from($paymentMethod);
        $serviceArea = ServiceArea::from($this->activeServiceArea);
        $total = $this->cartTotal();

        $sale = DB::transaction(function () use ($method, $serviceArea, $total, $amountGiven, $changeDue) {
            $sale = Sale::create([
                'receipt_number' => Sale::nextReceiptNumber(),
                'user_id' => Auth::id(),
                'payment_method' => $method,
                'service_area' => $serviceArea,
                'sale_status' => SaleStatus::Completed,
                'total_amount' => $total,
                'amount_given' => $amountGiven,
                'change_due' => $changeDue,
            ]);

            foreach ($this->cart as $productId => $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'product_name' => $item['name'],
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            return $sale;
        });

        $this->lastSaleReceipt = [
            'id' => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'items' => $this->cart,
            'total' => $total,
            'payment_method' => $method,
            'service_area' => $serviceArea,
            'created_at' => now(),
            'cashier' => Auth::user()->name,
            'amount_given' => $amountGiven,
            'change_due' => $changeDue,
        ];
        $this->cart = [];
        $this->showCheckout = false;
        $this->checkoutMethod = null;
        $this->amountGiven = '';
        unset($this->recentSales);
    }

    public function completeClientSale(array $items, string $paymentMethod, ?int $amountGiven = null, ?int $changeDue = null, ?string $serviceArea = null): void
    {
        if ($this->todayClosing) {
            return;
        }

        $serviceArea = ServiceArea::from($serviceArea ?? $this->activeServiceArea);
        $cart = $this->validatedClientCart($items, $serviceArea);

        if (empty($cart)) {
            return;
        }

        $method = PaymentMethod::from($paymentMethod);
        $total = array_sum(array_map(
            fn (array $item) => $item['price'] * $item['quantity'],
            $cart
        ));

        if ($method === PaymentMethod::Cash) {
            if ($amountGiven === null || $amountGiven < $total) {
                throw ValidationException::withMessages([
                    'amountGiven' => 'Le montant donné est insuffisant.',
                ]);
            }

            $changeDue = $amountGiven - $total;
        } else {
            $amountGiven = null;
            $changeDue = null;
        }

        $sale = DB::transaction(function () use ($cart, $method, $serviceArea, $total, $amountGiven, $changeDue) {
            $sale = Sale::create([
                'receipt_number' => Sale::nextReceiptNumber(),
                'user_id' => Auth::id(),
                'payment_method' => $method,
                'service_area' => $serviceArea,
                'sale_status' => SaleStatus::Completed,
                'total_amount' => $total,
                'amount_given' => $amountGiven,
                'change_due' => $changeDue,
            ]);

            foreach ($cart as $productId => $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'product_name' => $item['name'],
                    'unit_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            return $sale;
        });

        $this->lastSaleReceipt = [
            'id' => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'items' => $cart,
            'total' => $total,
            'payment_method' => $method,
            'service_area' => $serviceArea,
            'created_at' => now(),
            'cashier' => Auth::user()->name,
            'amount_given' => $amountGiven,
            'change_due' => $changeDue,
        ];
        $this->showCheckout = false;
        $this->checkoutMethod = null;
        $this->amountGiven = '';
        unset($this->recentSales);
    }

    protected function validatedClientCart(array $items, ServiceArea $serviceArea): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        $products = Product::where('is_active', true)
            ->where('service_area', $serviceArea)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($items)
            ->mapWithKeys(function (array $item) use ($products) {
                $productId = (int) ($item['product_id'] ?? 0);
                $product = $products->get($productId);

                if (! $product) {
                    return [];
                }

                $quantity = min(max((int) ($item['quantity'] ?? 0), 0), 999);

                if ($quantity < 1) {
                    return [];
                }

                return [
                    $productId => [
                        'name' => $product->name,
                        'emoji' => $product->emoji,
                        'price' => $product->price,
                        'quantity' => $quantity,
                    ],
                ];
            })
            ->all();
    }

    public function closeReceipt(): void
    {
        $this->lastSaleReceipt = null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pos.terminal');
    }
}
