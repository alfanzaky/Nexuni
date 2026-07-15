<?php

namespace Tests\Feature\Product\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductTableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_providers_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('providers'), 'Table providers does not exist.');

        $this->assertTrue(Schema::hasColumns('providers', [
            'id',
            'code',          // e.g., TSEL, ISAT, PLN
            'name',          // Telkomsel, Indosat, PLN
            'is_active',
            'created_at',
            'updated_at',
        ]), 'Table providers does not have the expected columns.');
    }

    public function test_categories_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('categories'), 'Table categories does not exist.');

        $this->assertTrue(Schema::hasColumns('categories', [
            'id',
            'code',          // e.g., PULSA, DATA, TOKEN
            'name',
            'type',          // e.g., prepaid, postpaid
            'is_active',
            'created_at',
            'updated_at',
        ]), 'Table categories does not have the expected columns.');
    }

    public function test_products_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('products'), 'Table products does not exist.');

        $this->assertTrue(Schema::hasColumns('products', [
            'id',
            'provider_id',
            'category_id',
            'code',          // e.g., S10, I10
            'name',          // Telkomsel 10.000
            'description',
            'price',         // base price from supplier/engine
            'is_active',
            'created_at',
            'updated_at',
        ]), 'Table products does not have the expected columns.');
    }
}
