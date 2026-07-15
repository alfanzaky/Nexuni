<?php

namespace Tests\Feature\Reseller\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerTableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_groups_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('reseller_groups'), 'Table reseller_groups does not exist.');

        $this->assertTrue(Schema::hasColumns('reseller_groups', [
            'id',
            'name',          // e.g., VIP, Gold, Reguler
            'level',         // numeric level for hierarchy
            'description',
            'created_at',
            'updated_at',
        ]), 'Table reseller_groups does not have the expected columns.');
    }

    public function test_resellers_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('resellers'), 'Table resellers does not exist.');

        $this->assertTrue(Schema::hasColumns('resellers', [
            'id',
            'user_id',       // Foreign key to users
            'group_id',      // Foreign key to reseller_groups
            'status',        // active, suspended, etc.
            'created_at',
            'updated_at',
        ]), 'Table resellers does not have the expected columns.');
    }
}
