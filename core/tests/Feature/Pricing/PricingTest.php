<?php

namespace Tests\Feature\Pricing;

use App\Domains\Pricing\DTOs\CalculatePriceData;
use App\Domains\Pricing\DTOs\CreateMarginData;
use App\Domains\Pricing\Services\PricingService;
use App\Domains\Product\Models\Category;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\Provider;
use App\Domains\Reseller\Models\ResellerGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;

    private ResellerGroup $group;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = $this->app->make(PricingService::class);

        $this->group = ResellerGroup::create(['name' => 'Reguler', 'level' => 3]);

        $provider = Provider::create(['code' => 'TSEL', 'name' => 'Telkomsel']);
        $category = Category::create(['code' => 'PULSA', 'name' => 'Pulsa']);

        $this->product = Product::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'code' => 'T10',
            'name' => 'Telkomsel 10K',
            'price' => 10000,
        ]);
    }

    public function test_calculate_final_price_without_margin()
    {
        $data = new CalculatePriceData($this->product->id, $this->group->id);
        $finalPrice = $this->pricingService->calculateFinalPrice($data);

        $this->assertEquals(10000, $finalPrice);
    }

    public function test_calculate_final_price_with_global_margin()
    {
        $this->pricingService->setMargin(new CreateMarginData(
            resellerGroupId: $this->group->id,
            amount: 500
        ));

        $data = new CalculatePriceData($this->product->id, $this->group->id);
        $finalPrice = $this->pricingService->calculateFinalPrice($data);

        $this->assertEquals(10500, $finalPrice);
    }

    public function test_calculate_final_price_with_percentage_margin()
    {
        $this->pricingService->setMargin(new CreateMarginData(
            resellerGroupId: $this->group->id,
            percentage: 2.5 // 2.5% of 10000 = 250
        ));

        $data = new CalculatePriceData($this->product->id, $this->group->id);
        $finalPrice = $this->pricingService->calculateFinalPrice($data);

        $this->assertEquals(10250, $finalPrice);
    }
}
