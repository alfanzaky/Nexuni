<?php

namespace Tests\Feature\Financial\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialTableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallets_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('wallets'));
        $this->assertTrue(Schema::hasColumns('wallets', [
            'id',
            'user_id',
            'available_balance',
            'held_balance',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_wallet_ledgers_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('wallet_ledgers'));
        $this->assertTrue(Schema::hasColumns('wallet_ledgers', [
            'id',
            'wallet_id',
            'type',
            'amount',
            'balance_before',
            'balance_after',
            'description',
            'reference_type',
            'reference_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_deposits_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('deposits'));
        $this->assertTrue(Schema::hasColumns('deposits', [
            'id',
            'user_id',
            'amount',
            'status',
            'payment_method',
            'created_at',
            'updated_at',
        ]));
    }
}
