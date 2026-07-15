<?php

namespace App\Domains\Deposit\Actions;

use App\Domains\Deposit\DTOs\ApproveDepositData;
use App\Domains\Deposit\Models\Deposit;
use App\Domains\Financial\DTOs\MutateWalletData;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Financial\Services\WalletLedgerService;
use Exception;
use Illuminate\Support\Facades\DB;

class ApproveDeposit
{
    public function __construct(
        private readonly WalletLedgerService $ledgerService
    ) {}

    /**
     * @throws Exception
     */
    public function execute(ApproveDepositData $data): Deposit
    {
        return DB::transaction(function () use ($data) {
            $deposit = Deposit::lockForUpdate()->findOrFail($data->depositId);

            if ($deposit->status !== 'pending') {
                throw new Exception('Only pending deposits can be approved.');
            }

            $deposit->status = 'approved';
            $deposit->approved_by_user_id = $data->approvedByUserId;
            $deposit->save();

            $wallet = Wallet::where('user_id', $deposit->user_id)->firstOrFail();

            $mutateData = new MutateWalletData(
                walletId: $wallet->id,
                type: 'credit',
                amount: $deposit->amount,
                description: 'Deposit approval',
                reference: $deposit
            );

            $this->ledgerService->mutate($mutateData);

            return $deposit;
        });
    }
}
