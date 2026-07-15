<?php

namespace Tests\Feature\Pricing\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PricingTableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_margins_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('margins'), 'Table margins does not exist.');

        $this->assertTrue(Schema::hasColumns('margins', [
            'id',
            'reseller_group_id',
            'category_id',   // Can be null if applying to all categories
            'provider_id',   // Can be null if applying to all providers
            'product_id',    // Can be null if applying to category/provider
            'amount',        // Fixed amount margin
            'percentage',    // Percentage margin
            'is_active',
            'created_at',
            'updated_at',
        ]), 'Table margins does not have the expected columns.');
    }

    public function test_pricings_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('pricings'), 'Table pricings does not exist.');

        $this->assertTrue(Schema::hasColumns('pricings', [
            'id',
            'product_id',
            'base_price',    // Price from supplier
            'final_price',   // Cached final price after base margins, optional
            'created_at',
            'updated_at',
        ]), 'Table pricings does not have the expected columns.');
    }
}
