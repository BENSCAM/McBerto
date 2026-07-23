<?php

namespace App\Livewire\Pos;

use App\Enums\PaymentMethod;
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

    /** @var array<int, array{name: string, emoji: ?string, price: int, quantity: int}> */
    public array $cart = [];

    public bool $showCheckout = false;

    public ?int $lastSaleId = null;

    public function mount(): void
    {
        $this->activeCategoryId = $this->categories()->first()?->id;
    }

    #[Computed]
    public function categoriesWithProducts()
    {
        return $this->categories();
    }

    protected function categories()
    {
        return Category::where('is_active', true)
            ->with(['products' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
    }

    public function addToCart(int $productId): void
    {
        $product = Product::findOrFail($productId);

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

    #[Computed]
    public function cartTotal(): int
    {
        return array_sum(array_map(
            fn ($item) => $item['price'] * $item['quantity'],
            $this->cart
        ));
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
    }

    public function completeSale(string $paymentMethod): void
    {
        if (empty($this->cart)) {
            return;
        }

        $method = PaymentMethod::from($paymentMethod);
        $total = $this->cartTotal();

        $saleId = DB::transaction(function () use ($method, $total) {
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'payment_method' => $method,
                'total_amount' => $total,
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

        $this->lastSaleId = $saleId;
        $this->cart = [];
        $this->showCheckout = false;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pos.terminal');
    }
}
