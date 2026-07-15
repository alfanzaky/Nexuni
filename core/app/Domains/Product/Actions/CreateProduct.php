<?php

namespace App\Domains\Product\Actions;

use App\Domains\Product\DTOs\CreateProductData;
use App\Domains\Product\Models\Product;

class CreateProduct
{
    public function execute(CreateProductData $data): Product
    {
        return Product::create([
            'provider_id' => $data->providerId,
            'category_id' => $data->categoryId,
            'code' => $data->code,
            'name' => $data->name,
            'price' => $data->price,
            'description' => $data->description,
            'is_active' => $data->isActive,
        ]);
    }
}
