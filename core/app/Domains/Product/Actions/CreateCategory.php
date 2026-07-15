<?php

namespace App\Domains\Product\Actions;

use App\Domains\Product\DTOs\CreateCategoryData;
use App\Domains\Product\Models\Category;

class CreateCategory
{
    public function execute(CreateCategoryData $data): Category
    {
        return Category::create([
            'code' => $data->code,
            'name' => $data->name,
            'type' => $data->type,
            'is_active' => $data->isActive,
        ]);
    }
}
