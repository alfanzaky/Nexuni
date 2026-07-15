<?php

namespace App\Domains\Transaction\Listeners;

use App\Domains\Transaction\Events\TransactionCreatedEvent;
use App\Domains\Transaction\Models\OutboxMessage;

class PublishTransactionToRabbitMQ
{
    public function handle(TransactionCreatedEvent $event): void
    {
        $transaction = $event->transaction;

        // Outbox Pattern: persist the message to the database FIRST.
        // A scheduled job (PublishOutboxMessagesJob) will reliably pick this up
        // and forward it to RabbitMQ, even if the broker is temporarily unavailable.
        // This guarantees at-least-once delivery without orphaning transactions.
        OutboxMessage::create([
            'event_type' => 'transaction.created',
            'payload' => [
                'action' => 'purchase',
                'transaction_id' => $transaction->transaction_id,
                'product_id' => $transaction->product_id,
                'provider_id' => $transaction->provider_id,
                'destination' => $transaction->destination,
                'amount' => (string) $transaction->amount,
                'idempotency_key' => $transaction->idempotency_key,
                'timestamp' => $transaction->created_at->toIso8601String(),
            ],
            'status' => 'pending',
        ]);
    }
}
