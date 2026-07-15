<?php

namespace Tests\Feature\Financial;

use App\Domains\Financial\DTOs\MutateWalletData;
use App\Domains\Financial\Enums\LedgerType;
use App\Domains\Financial\Enums\WalletStatus;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Financial\Services\WalletLedgerService;
use App\Domains\Identity\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    private WalletLedgerService $ledgerService;

    private User $user;

    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = $this->app->make(WalletLedgerService::class);

        $this->user = User::factory()->create();

        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'status' => WalletStatus::ACTIVE,
        ]);

        // Ensure starting balance is 0
        $this->assertEquals(0, $this->wallet->available_balance);
    }

    public function test_can_credit_wallet_and_create_ledger()
    {
        $data = new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::CREDIT,
            amount: '150000.00',
            description: 'Initial deposit'
        );

        $ledger = $this->ledgerService->mutate($data);

        $this->assertEquals(150000.00, $ledger->amount);
        $this->assertEquals(0, $ledger->balance_before);
        $this->assertEquals(150000.00, $ledger->balance_after);
        $this->assertEquals(LedgerType::CREDIT, $ledger->type);

        $this->wallet->refresh();
        $this->assertEquals(150000.00, $this->wallet->available_balance);
    }

    public function test_can_debit_wallet_with_sufficient_balance()
    {
        // First credit
        $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::CREDIT,
            amount: '50000.00',
            description: 'Credit'
        ));

        // Then debit
        $ledger = $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::DEBIT,
            amount: '20000.00',
            description: 'Payment'
        ));

        $this->assertEquals(20000.00, $ledger->amount);
        $this->assertEquals(50000.00, $ledger->balance_before);
        $this->assertEquals(30000.00, $ledger->balance_after);
        $this->assertEquals(LedgerType::DEBIT, $ledger->type);

        $this->wallet->refresh();
        $this->assertEquals(30000.00, $this->wallet->available_balance);
    }

    public function test_cannot_debit_with_insufficient_balance()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient balance.');

        $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::DEBIT,
            amount: '10000.00',
            description: 'Payment'
        ));
    }
}
