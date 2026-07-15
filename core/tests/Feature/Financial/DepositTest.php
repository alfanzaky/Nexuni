<?php

namespace Tests\Feature\Financial;

use App\Domains\Deposit\Actions\ApproveDeposit;
use App\Domains\Deposit\Actions\RequestDeposit;
use App\Domains\Deposit\DTOs\ApproveDepositData;
use App\Domains\Deposit\DTOs\RequestDepositData;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $admin;

    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();

        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
    }

    public function test_can_request_deposit()
    {
        $action = $this->app->make(RequestDeposit::class);
        $data = new RequestDepositData(
            userId: $this->user->id,
            amount: 500000.00,
            paymentMethod: 'Bank Transfer'
        );

        $deposit = $action->execute($data);

        $this->assertDatabaseHas('deposits', [
            'id' => $deposit->id,
            'user_id' => $this->user->id,
            'amount' => 500000.00,
            'status' => 'pending',
            'payment_method' => 'Bank Transfer',
        ]);
    }

    public function test_approve_deposit_updates_wallet_balance()
    {
        $requestAction = $this->app->make(RequestDeposit::class);
        $deposit = $requestAction->execute(new RequestDepositData(
            userId: $this->user->id,
            amount: 500000.00
        ));

        $this->assertEquals(0, $this->wallet->fresh()->available_balance);

        $approveAction = $this->app->make(ApproveDeposit::class);
        $approveAction->execute(new ApproveDepositData(
            depositId: $deposit->id,
            approvedByUserId: $this->admin->id
        ));

        // Assert deposit is approved
        $this->assertEquals('approved', $deposit->fresh()->status);
        $this->assertEquals($this->admin->id, $deposit->fresh()->approved_by_user_id);

        // Assert wallet is updated
        $this->assertEquals(500000.00, $this->wallet->fresh()->available_balance);

        // Assert ledger is created correctly with polymorphic relation
        $this->assertDatabaseHas('wallet_ledgers', [
            'wallet_id' => $this->wallet->id,
            'type' => 'credit',
            'amount' => 500000.00,
            'reference_type' => get_class($deposit),
            'reference_id' => $deposit->id,
        ]);
    }
}
