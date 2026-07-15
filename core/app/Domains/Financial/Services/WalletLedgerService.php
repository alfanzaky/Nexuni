<?php

namespace App\Domains\Financial\Services;

use App\Domains\Financial\DTOs\MutateWalletData;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Financial\Models\WalletLedger;
use Exception;
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

        if (! in_array($data->type, ['credit', 'debit'])) {
            throw new InvalidArgumentException('Mutation type must be credit or debit.');
        }

        return DB::transaction(function () use ($data) {
            // Pessimistic lock on the wallet row
            $wallet = Wallet::where('id', $data->walletId)->lockForUpdate()->firstOrFail();

            if ($wallet->status !== 'active') {
                throw new Exception('Cannot mutate inactive wallet.');
            }

            $balanceBefore = (string) $wallet->available_balance;

            if ($data->type === 'debit') {
                if (bccomp($balanceBefore, $data->amount, 2) === -1) {
                    throw new Exception('Insufficient balance.');
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
}
