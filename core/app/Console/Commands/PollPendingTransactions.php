<?php

namespace App\Console\Commands;

use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\OutboxMessage;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Console\Command;

class PollPendingTransactions extends Command
{
    protected $signature = 'transactions:poll-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll transactions that are stuck in PENDING status and queue a check_status action';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = now()->subMinute();

        $query = Transaction::where('status', TransactionStatus::PENDING)
            ->where('created_at', '<=', $cutoff);

        $count = $query->count();
        if ($count === 0) {
            return;
        }

        $this->info("Found {$count} pending transaction(s) to poll.");

        $query->chunk(100, function ($pendingTransactions) {
            foreach ($pendingTransactions as $transaction) {
                // Prevent duplicate polling messages if one is already queued or being published
                $existing = OutboxMessage::where('event_type', 'transaction.check_status')
                    ->whereJsonContains('payload->transaction_id', $transaction->transaction_id)
                    ->whereIn('status', ['pending', 'publishing'])
                    ->exists();

                if ($existing) {
                    continue;
                }

                OutboxMessage::create([
                    'event_type' => 'transaction.check_status',
                    'payload' => [
                        'action' => 'check_status',
                        'transaction_id' => $transaction->transaction_id,
                        'product_id' => $transaction->product_id,
                        'provider_id' => $transaction->provider_id,
                        'destination' => $transaction->destination,
                        'amount' => (string) $transaction->amount,
                        'idempotency_key' => $transaction->idempotency_key,
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'status' => 'pending',
                ]);
                $this->info("Queued status check for transaction: {$transaction->transaction_id}");
            }
        });
    }
}
