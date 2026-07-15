<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Financial\Services\WalletLedgerService;
use App\Domains\Identity\Models\User;
use App\Domains\Product\Exceptions\ProductInactiveException;
use App\Domains\Product\Models\Product;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionCreatedEvent;
use App\Domains\Transaction\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTransaction
{
    public function __construct(
        private readonly WalletLedgerService $ledgerService
    ) {}

    /**
     * @throws Exception
     */
    public function execute(CreateTransactionData $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $user = User::findOrFail($data->userId);

            // 1. Fetch and lock the user's active wallet FIRST.
            // This serializes all transaction creations for this user, preventing race conditions
            // where two concurrent requests with the same idempotency key both pass the check.
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();

            // 2. Check for idempotency to prevent duplicate transactions
            $existing = Transaction::where('user_id', $data->userId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            $product = Product::findOrFail($data->productId);

            if (! $product->is_active) {
                throw new ProductInactiveException('Cannot create transaction for an inactive product.');
            }

            // Calculate final price
            $finalPrice = (string) $product->price;

            // Hold the balance (this creates the DEBIT ledger)
            $ledger = $this->ledgerService->holdBalance($wallet->id, $finalPrice, 'Payment Hold for '.$product->name);

            // Generate unique transaction ID
            $transactionId = 'TRX-'.date('YmdHis').'-'.Str::random(6);

            // Create transaction in PENDING state
            $transaction = Transaction::create([
                'transaction_id' => $transactionId,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'provider_id' => $product->provider_id,
                'destination' => $data->destination,
                'amount' => $finalPrice,
                'status' => TransactionStatus::PENDING,
                'idempotency_key' => $data->idempotencyKey,
            ]);

            // Update ledger reference to the newly created transaction
            $ledger->reference()->associate($transaction);
            $ledger->save();

            // Create the outbox record INSIDE this transaction — not after commit.
            //
            // The Outbox Pattern's atomicity guarantee requires the outbox row and the
            // business data (wallet hold + transaction) to commit or roll back together.
            // Since OutboxMessage is a plain DB record, it participates in the same
            // transaction automatically — no DB::afterCommit() needed here.
            //
            // If this transaction rolls back (e.g., a concurrent DB constraint), the
            // outbox row is rolled back too, so the Go Engine never receives a phantom message.
            event(new TransactionCreatedEvent($transaction));

            return $transaction;
        });
    }
}
