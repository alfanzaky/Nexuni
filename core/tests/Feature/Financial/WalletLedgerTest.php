<?php

namespace Tests\Feature\Financial;

use App\Domains\Financial\DTOs\MutateWalletData;
use App\Domains\Financial\Enums\LedgerType;
use App\Domains\Financial\Enums\WalletStatus;
use App\Domains\Financial\Exceptions\WalletInactiveException;
use App\Domains\Financial\Exceptions\WalletInsufficientBalanceException;
use App\Domains\Financial\Exceptions\WalletInsufficientHeldBalanceException;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Financial\Models\WalletLedger;
use App\Domains\Financial\Services\WalletLedgerService;
use App\Domains\Identity\Models\User;
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
        $this->expectException(WalletInsufficientBalanceException::class);
        $this->expectExceptionMessage('Insufficient balance.');

        $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::DEBIT,
            amount: '10000.00',
            description: 'Payment'
        ));
    }

    public function test_can_hold_balance_and_create_ledger()
    {
        $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::CREDIT,
            amount: '10000.00',
            description: 'Initial deposit'
        ));

        $ledger = $this->ledgerService->holdBalance($this->wallet->id, '1000.00', 'Hold payment');

        $this->wallet->refresh();
        $this->assertEquals(9000.00, $this->wallet->available_balance);
        $this->assertEquals(1000.00, $this->wallet->held_balance);

        $this->assertDatabaseHas('wallet_ledgers', [
            'id' => $ledger->id,
            'type' => LedgerType::DEBIT->value,
            'amount' => 1000.00,
            'balance_before' => 10000.00,
            'balance_after' => 9000.00,
            'description' => 'Hold payment',
        ]);
    }

    public function test_can_release_hold_balance_and_create_refund_ledger()
    {
        $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::CREDIT,
            amount: '10000.00',
            description: 'Initial deposit'
        ));

        $this->ledgerService->holdBalance($this->wallet->id, '1000.00', 'Hold payment');

        $refundLedger = $this->ledgerService->releaseHoldBalance($this->wallet->id, '1000.00', 'Refund failed transaction');

        $this->wallet->refresh();
        $this->assertEquals(10000.00, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->held_balance);

        $this->assertDatabaseHas('wallet_ledgers', [
            'id' => $refundLedger->id,
            'type' => LedgerType::CREDIT->value,
            'amount' => 1000.00,
            'balance_before' => 9000.00,
            'balance_after' => 10000.00,
            'description' => 'Refund failed transaction',
        ]);
    }

    public function test_can_capture_hold_balance_without_creating_new_ledger()
    {
        $this->ledgerService->mutate(new MutateWalletData(
            walletId: $this->wallet->id,
            type: LedgerType::CREDIT,
            amount: '10000.00',
            description: 'Initial deposit'
        ));

        $this->ledgerService->holdBalance($this->wallet->id, '1000.00', 'Hold payment');

        // Count ledgers before capture
        $countBefore = WalletLedger::count();

        $this->ledgerService->captureHoldBalance($this->wallet->id, '1000.00');

        $this->wallet->refresh();
        $this->assertEquals(9000.00, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->held_balance);

        // Assert no new ledger was created
        $this->assertEquals($countBefore, WalletLedger::count());
    }

    public function test_cannot_release_hold_with_insufficient_held_balance()
    {
        $this->expectException(WalletInsufficientHeldBalanceException::class);
        $this->expectExceptionMessage('Insufficient held balance.');

        $this->ledgerService->releaseHoldBalance($this->wallet->id, '1000.00', 'Refund failed transaction');
    }

    public function test_release_hold_throws_exception_on_inactive_wallet_without_force()
    {
        $this->ledgerService->mutate(new MutateWalletData($this->wallet->id, LedgerType::CREDIT, '10000.00', 'Deposit'));
        $this->ledgerService->holdBalance($this->wallet->id, '1000.00', 'Hold');

        $this->wallet->update(['status' => WalletStatus::LOCKED]);

        $this->expectException(WalletInactiveException::class);
        $this->ledgerService->releaseHoldBalance($this->wallet->id, '1000.00', 'Refund');
    }

    public function test_capture_hold_throws_exception_on_inactive_wallet_without_force()
    {
        $this->ledgerService->mutate(new MutateWalletData($this->wallet->id, LedgerType::CREDIT, '10000.00', 'Deposit'));
        $this->ledgerService->holdBalance($this->wallet->id, '1000.00', 'Hold');

        $this->wallet->update(['status' => WalletStatus::LOCKED]);

        $this->expectException(WalletInactiveException::class);
        $this->ledgerService->captureHoldBalance($this->wallet->id, '1000.00');
    }

    public function test_can_release_and_capture_on_inactive_wallet_with_force()
    {
        $this->ledgerService->mutate(new MutateWalletData($this->wallet->id, LedgerType::CREDIT, '10000.00', 'Deposit'));
        $this->ledgerService->holdBalance($this->wallet->id, '1000.00', 'Hold');

        $this->wallet->update(['status' => WalletStatus::LOCKED]);

        // Release with force = true
        $this->ledgerService->releaseHoldBalance($this->wallet->id, '500.00', 'Refund', null, true);

        // Capture with force = true
        $this->ledgerService->captureHoldBalance($this->wallet->id, '500.00', true);

        $this->wallet->refresh();
        $this->assertEquals(9500.00, $this->wallet->available_balance);
        $this->assertEquals(0, $this->wallet->held_balance);
    }
}
