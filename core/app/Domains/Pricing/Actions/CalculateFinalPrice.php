<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Pricing\DTOs\CalculatePriceData;
use App\Domains\Pricing\Models\Margin;
use App\Domains\Product\Models\Product;

class CalculateFinalPrice
{
    public function execute(CalculatePriceData $data): float
    {
        $product = Product::findOrFail($data->productId);
        $basePrice = $product->price;

        $margin = Margin::where('reseller_group_id', $data->resellerGroupId)
            ->where('is_active', true)
            ->where(function ($query) use ($product) {
                // Priority 1: Specific product
                $query->where('product_id', $product->id)
                    // Priority 2: Specific provider AND specific category
                    ->orWhere(function ($q) use ($product) {
                        $q->whereNull('product_id')
                            ->where('provider_id', $product->provider_id)
                            ->where('category_id', $product->category_id);
                    })
                    // Priority 3: Specific provider only
                    ->orWhere(function ($q) use ($product) {
                        $q->whereNull('product_id')
                            ->where('provider_id', $product->provider_id)
                            ->whereNull('category_id');
                    })
                    // Priority 4: Specific category only
                    ->orWhere(function ($q) use ($product) {
                        $q->whereNull('product_id')
                            ->whereNull('provider_id')
                            ->where('category_id', $product->category_id);
                    })
                    // Priority 5: Global margin
                    ->orWhere(function ($q) {
                        $q->whereNull('product_id')
                            ->whereNull('provider_id')
                            ->whereNull('category_id');
                    });
            })
            ->orderByRaw('product_id DESC NULLS LAST')
            ->orderByRaw('provider_id DESC NULLS LAST')
            ->orderByRaw('category_id DESC NULLS LAST')
            ->first();

        if (! $margin) {
            return $basePrice;
        }

        $amountToAdd = $margin->amount;
        $percentageToAdd = ($basePrice * $margin->percentage) / 100;

        return $basePrice + $amountToAdd + $percentageToAdd;
    }
}
