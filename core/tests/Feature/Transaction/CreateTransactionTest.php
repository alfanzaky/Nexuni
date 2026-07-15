<?php

namespace Tests\Feature\Transaction;

use App\Domains\Financial\DTOs\MutateWalletData;
use App\Domains\Financial\Enums\LedgerType;
use App\Domains\Financial\Enums\WalletStatus;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Financial\Services\WalletLedgerService;
use App\Domains\Identity\Models\User;
use App\Domains\Product\Models\Category;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\Provider;
use App\Domains\Transaction\Actions\CreateTransaction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $wallet;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'status' => WalletStatus::ACTIVE,
        ]);

        $ledgerService = $this->app->make(WalletLedgerService::class);
        $ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::CREDIT,
            amount: '50000.00',
            description: 'Initial balance'
        ));

        $provider = Provider::create([
            'name' => 'Telkomsel',
            'code' => 'TSEL',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Pulsa',
            'code' => 'PULSA',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Pulsa 10k',
            'code' => 'TSEL10',
            'category_id' => $category->id,
            'provider_id' => $provider->id,
            'price' => '10000.00',
            'is_active' => true,
        ]);
    }

    public function test_can_create_transaction_and_hold_balance()
    {
        $action = $this->app->make(CreateTransaction::class);

        $data = new CreateTransactionData(
            userId: $this->user->id,
            productId: $this->product->id,
            destination: '08123456789',
            idempotencyKey: 'IDEM-123'
        );

        $transaction = $action->execute($data);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'destination' => '08123456789',
            'amount' => 10000.00,
            'status' => TransactionStatus::PENDING->value,
            'idempotency_key' => 'IDEM-123',
        ]);

        $this->wallet->refresh();

        $this->assertEquals(40000.00, $this->wallet->available_balance);
        $this->assertEquals(10000.00, $this->wallet->held_balance);
    }

    public function test_duplicate_transaction_with_same_idempotency_returns_existing()
    {
        $action = $this->app->make(CreateTransaction::class);

        $data = new CreateTransactionData(
            userId: $this->user->id,
            productId: $this->product->id,
            destination: '08123456789',
            idempotencyKey: 'IDEM-123'
        );

        $transaction1 = $action->execute($data);
        $transaction2 = $action->execute($data);

        $this->assertEquals($transaction1->id, $transaction2->id);

        $this->wallet->refresh();

        // Balance should only be held ONCE
        $this->assertEquals(40000.00, $this->wallet->available_balance);
        $this->assertEquals(10000.00, $this->wallet->held_balance);
    }

    public function test_cannot_create_transaction_for_inactive_product()
    {
        $this->product->update(['is_active' => false]);

        $this->expectException(\App\Domains\Product\Exceptions\ProductInactiveException::class);
        $this->expectExceptionMessage('Cannot create transaction for an inactive product.');

        $action = $this->app->make(CreateTransaction::class);

        $data = new CreateTransactionData(
            userId: $this->user->id,
            productId: $this->product->id,
            destination: '08123456789',
            idempotencyKey: 'IDEM-124'
        );

        $action->execute($data);
    }
}
