<?php

namespace Tests\Feature\Transaction\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionTableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('transactions', [
                'id',
                'transaction_id',
                'user_id',
                'product_id',
                'provider_id',
                'destination',
                'amount',
                'status',
                'supplier_id',
                'idempotency_key',
                'created_at',
                'updated_at',
            ]),
            'Transactions table is missing one or more expected columns.'
        );
    }

    public function test_suppliers_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('suppliers', [
                'id',
                'name',
                'code',
                'status',
                'created_at',
                'updated_at',
            ]),
            'Suppliers table is missing one or more expected columns.'
        );
    }
}
