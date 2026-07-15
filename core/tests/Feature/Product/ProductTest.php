<?php

namespace Tests\Feature\Product;

use App\Domains\Product\DTOs\CreateCategoryData;
use App\Domains\Product\DTOs\CreateProductData;
use App\Domains\Product\DTOs\CreateProviderData;
use App\Domains\Product\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = $this->app->make(ProductService::class);
    }

    public function test_can_create_provider_category_and_product()
    {
        $provider = $this->productService->createProvider(new CreateProviderData(
            code: 'TSEL',
            name: 'Telkomsel'
        ));

        $category = $this->productService->createCategory(new CreateCategoryData(
            code: 'PULSA',
            name: 'Pulsa Reguler'
        ));

        $product = $this->productService->createProduct(new CreateProductData(
            providerId: $provider->id,
            categoryId: $category->id,
            code: 'S10',
            name: 'Telkomsel 10.000',
            price: 10050.00
        ));

        $this->assertEquals('S10', $product->code);
        $this->assertEquals($provider->id, $product->provider_id);
        $this->assertEquals($category->id, $product->category_id);
        $this->assertEquals(10050.00, $product->price);

        $this->assertDatabaseHas('products', [
            'code' => 'S10',
            'price' => 10050.00,
        ]);
    }
}
