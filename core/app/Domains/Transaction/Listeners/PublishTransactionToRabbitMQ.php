<?php

namespace App\Domains\Transaction\Listeners;

use App\Domains\Transaction\Events\TransactionCreatedEvent;
use App\Domains\Transaction\Services\RabbitMQPublisherService;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

class PublishTransactionToRabbitMQ
{
    public function __construct(
        private readonly RabbitMQPublisherService $publisher
    ) {}

    public function handle(TransactionCreatedEvent $event): void
    {
        $transaction = $event->transaction;

        $payload = [
            'transaction_id' => $transaction->transaction_id,
            'product_id' => $transaction->product_id,
            'provider_id' => $transaction->provider_id,
            'destination' => $transaction->destination,
            'amount' => (string) $transaction->amount,
            'idempotency_key' => $transaction->idempotency_key,
            'timestamp' => $transaction->created_at->toIso8601String(),
        ];

        try {
            $channel = $this->publisher->getChannel();
            $queue = config('rabbitmq.queues.transaction');

            $channel->queue_declare($queue, false, true, false, false);

            $msg = new AMQPMessage(
                json_encode($payload),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $channel->basic_publish($msg, '', $queue);
        } catch (\Exception $e) {
            // WARNING: Transaction is CREATED but NOT dispatched to the Go Engine.
            // This transaction will remain in PENDING state indefinitely.
            // Operator action required: implement an Outbox Pattern or a reconciliation
            // job to re-publish orphaned PENDING transactions.
            Log::critical('CRITICAL: Failed to publish transaction to RabbitMQ. Transaction may be orphaned.', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
