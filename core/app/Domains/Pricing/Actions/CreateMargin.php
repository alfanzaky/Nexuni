<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Pricing\DTOs\CreateMarginData;
use App\Domains\Pricing\Models\Margin;

class CreateMargin
{
    public function execute(CreateMarginData $data): Margin
    {
        $query = Margin::query()->where('reseller_group_id', $data->resellerGroupId);

        $data->categoryId ? $query->where('category_id', $data->categoryId) : $query->whereNull('category_id');
        $data->providerId ? $query->where('provider_id', $data->providerId) : $query->whereNull('provider_id');
        $data->productId ? $query->where('product_id', $data->productId) : $query->whereNull('product_id');

        $margin = $query->first();

        if ($margin) {
            $margin->update([
                'amount' => $data->amount,
                'percentage' => $data->percentage,
                'is_active' => $data->isActive,
            ]);

            return $margin;
        }

        return Margin::create([
            'reseller_group_id' => $data->resellerGroupId,
            'category_id' => $data->categoryId,
            'provider_id' => $data->providerId,
            'product_id' => $data->productId,
            'amount' => $data->amount,
            'percentage' => $data->percentage,
            'is_active' => $data->isActive,
        ]);
    }
}
