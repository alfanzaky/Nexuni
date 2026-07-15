<?php

namespace App\Domains\Pricing\Actions;

use App\Domains\Pricing\DTOs\CreateMarginData;
use App\Domains\Pricing\Models\Margin;

class CreateMargin
{
    public function execute(CreateMarginData $data): Margin
    {
        return Margin::updateOrCreate(
            [
                'reseller_group_id' => $data->resellerGroupId,
                'category_id' => $data->categoryId,
                'provider_id' => $data->providerId,
                'product_id' => $data->productId,
            ],
            [
                'amount' => $data->amount,
                'percentage' => $data->percentage,
                'is_active' => $data->isActive,
            ]
        );
    }
}
