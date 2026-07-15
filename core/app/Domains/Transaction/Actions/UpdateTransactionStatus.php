<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Financial\Services\WalletLedgerService;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateTransactionStatus
{
    public function __construct(
        private readonly WalletLedgerService $ledgerService
    ) {}

    /**
     * @throws Exception
     */
    public function execute(string $transactionId, TransactionStatus $status, string $message = '', string $sn = ''): Transaction
    {
        return DB::transaction(function () use ($transactionId, $status, $sn, $message) {
            // Lock the transaction row
            $transaction = Transaction::where('transaction_id', $transactionId)->lockForUpdate()->firstOrFail();

            // If transaction is already final, ignore
            if ($transaction->status !== TransactionStatus::PENDING) {
                return $transaction;
            }

            // Update transaction details
            $transaction->status = $status;
            $transaction->sn = $sn ?: null;
            $transaction->message = $message ?: null;
            $transaction->save();

            // Handle wallet balances via WalletLedgerService based on business rules
            if ($status === TransactionStatus::SUCCESS) {
                // Money is permanently taken
                $this->ledgerService->captureHoldBalance(
                    $transaction->wallet_id ?? $transaction->user->wallet->id,
                    (string) $transaction->amount,
                    force: true
                );
            } elseif ($status === TransactionStatus::FAILED) {
                // Money is returned to available balance
                $this->ledgerService->releaseHoldBalance(
                    $transaction->wallet_id ?? $transaction->user->wallet->id,
                    (string) $transaction->amount,
                    "Refund for failed transaction {$transactionId}",
                    $transaction,
                    force: true
                );
            }

            return $transaction;
        });
    }
}
