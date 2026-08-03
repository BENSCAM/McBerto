<?php

namespace App\Support;

use App\Enums\PaymentMethod;
use App\Enums\ServiceArea;
use App\Models\Category;
use App\Models\Product;

class OfflineCatalog
{
    public static function make(): array
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
