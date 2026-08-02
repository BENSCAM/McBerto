<?php

namespace App\Livewire\Pos;

use App\Enums\PaymentMethod;
use App\Enums\ServiceArea;
use App\Models\CashRegisterClosing;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
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

    /** @var array{id: int, items: array, total: int, payment_method: PaymentMethod, service_area: ServiceArea, created_at: \Illuminate\Support\Carbon, cashier: string, amount_given: ?int, change_due: ?int}|null */
    public ?array $lastSaleReceipt = null;

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
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
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

        $saleId = DB::transaction(function () use ($method, $serviceArea, $total, $amountGiven, $changeDue) {
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'payment_method' => $method,
                'service_area' => $serviceArea,
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

            return $sale->id;
        });

        $this->lastSaleReceipt = [
            'id' => $saleId,
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
