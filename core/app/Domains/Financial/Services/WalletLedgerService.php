<?php

namespace App\Domains\Financial\Services;

use App\Domains\Financial\DTOs\MutateWalletData;
use App\Domains\Financial\Enums\LedgerType;
use App\Domains\Financial\Enums\WalletStatus;
use App\Domains\Financial\Exceptions\WalletInactiveException;
use App\Domains\Financial\Exceptions\WalletInsufficientBalanceException;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Financial\Models\WalletLedger;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletLedgerService
{
    /**
     * Mutate wallet balance safely with pessimistic locking and ledger creation.
     *
     * @throws Exception
     */
    public function mutate(MutateWalletData $data): WalletLedger
    {
        if (bccomp($data->amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Mutation amount must be greater than zero.');
        }

        // DTO type is now LedgerType, no need for in_array validation here

        return DB::transaction(function () use ($data) {
            // Pessimistic lock on the wallet row
            $wallet = Wallet::where('id', $data->walletId)->lockForUpdate()->firstOrFail();

            if ($wallet->status !== WalletStatus::ACTIVE) {
                throw new WalletInactiveException('Cannot mutate inactive wallet.');
            }

            $balanceBefore = (string) $wallet->available_balance;

            if ($data->type === LedgerType::DEBIT) {
                if (bccomp($balanceBefore, $data->amount, 2) === -1) {
                    throw new WalletInsufficientBalanceException('Insufficient balance.');
                }
                $wallet->available_balance = bcsub($balanceBefore, $data->amount, 2);
            } else {
                $wallet->available_balance = bcadd($balanceBefore, $data->amount, 2);
            }

            $balanceAfter = $wallet->available_balance;
            $wallet->save();

            // Create ledger entry
            $ledger = new WalletLedger([
                'wallet_id' => $wallet->id,
                'type' => $data->type,
                'amount' => $data->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $data->description,
            ]);

            if ($data->reference) {
                $ledger->reference()->associate($data->reference);
            }

            $ledger->save();

            return $ledger;
        });
    }

    /**
     * @throws Exception
     */
    public function holdBalance(int $walletId, string $amount): void
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Hold amount must be greater than zero.');
        }

        DB::transaction(function () use ($walletId, $amount) {
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();

            if ($wallet->status !== WalletStatus::ACTIVE) {
                throw new WalletInactiveException('Cannot mutate inactive wallet.');
            }

            $balanceBefore = (string) $wallet->available_balance;

            if (bccomp($balanceBefore, $amount, 2) === -1) {
                throw new WalletInsufficientBalanceException('Insufficient balance.');
            }

            $wallet->available_balance = bcsub($balanceBefore, $amount, 2);
            $wallet->held_balance = bcadd((string) $wallet->held_balance, $amount, 2);
            $wallet->save();
        });
    }

    /**
     * @throws Exception
     */
    public function releaseHoldBalance(int $walletId, string $amount, string $description, ?Model $reference = null): void
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Release amount must be greater than zero.');
        }

        DB::transaction(function () use ($walletId, $amount) {
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $heldBefore = (string) $wallet->held_balance;

            if (bccomp($heldBefore, $amount, 2) === -1) {
                throw new Exception('Insufficient held balance.');
            }

            // Move from held to available
            $wallet->held_balance = bcsub($heldBefore, $amount, 2);
            $wallet->available_balance = bcadd((string) $wallet->available_balance, $amount, 2);
            $wallet->save();

            // NOTE: According to strict ledger rules, if we didn't create a DEBIT ledger when holding,
            // creating a CREDIT ledger upon release would inflate the derived balance.
            // But the architecture mentions "Refund" ledger.
            // We will NOT create a ledger here to keep available_balance = sum(ledgers).
            // If we need a FAILED ledger, we will adjust this logic later.
        });
    }

    /**
     * @throws Exception
     */
    public function captureHoldBalance(int $walletId, string $amount, string $description, ?Model $reference = null): WalletLedger
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Capture amount must be greater than zero.');
        }

        return DB::transaction(function () use ($walletId, $amount, $description, $reference) {
            $wallet = Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
            $heldBefore = (string) $wallet->held_balance;

            if (bccomp($heldBefore, $amount, 2) === -1) {
                throw new Exception('Insufficient held balance.');
            }

            // Deduct from held (money leaves system)
            $wallet->held_balance = bcsub($heldBefore, $amount, 2);
            $wallet->save();

            // Create ledger for the actual expense
            // To maintain available_balance = sum(ledgers), the balance_before in the ledger
            // is theoretically the available_balance BEFORE the hold was placed, or we just
            // treat available_balance + amount as the logical "before" state for this specific deduction.
            $logicalBalanceBefore = bcadd((string) $wallet->available_balance, $amount, 2);
            $logicalBalanceAfter = (string) $wallet->available_balance;

            $ledger = new WalletLedger([
                'wallet_id' => $wallet->id,
                'type' => LedgerType::DEBIT,
                'amount' => $amount,
                'balance_before' => $logicalBalanceBefore,
                'balance_after' => $logicalBalanceAfter,
                'description' => $description,
            ]);

            if ($reference) {
                $ledger->reference()->associate($reference);
            }

            $ledger->save();

            return $ledger;
        });
    }
}
