<?php

namespace App\Domains\Product\Services;

use App\Domains\Product\Actions\CreateCategory;
use App\Domains\Product\Actions\CreateProduct;
use App\Domains\Product\Actions\CreateProvider;
use App\Domains\Product\DTOs\CreateCategoryData;
use App\Domains\Product\DTOs\CreateProductData;
use App\Domains\Product\DTOs\CreateProviderData;
use App\Domains\Product\Models\Category;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\Provider;

class ProductService
{
    public function __construct(
        private readonly CreateProvider $createProvider,
        private readonly CreateCategory $createCategory,
        private readonly CreateProduct $createProduct,
    ) {}

    public function createProvider(CreateProviderData $data): Provider
    {
        return $this->createProvider->execute($data);
    }

    public function createCategory(CreateCategoryData $data): Category
    {
        return $this->createCategory->execute($data);
    }

    public function createProduct(CreateProductData $data): Product
    {
        return $this->createProduct->execute($data);
    }
}
