<?php

namespace App\Domains\Transaction\Listeners;

use App\Domains\Transaction\Events\TransactionCreatedEvent;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublishTransactionToRabbitMQ
{
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

        // Ensure this doesn't crash the transaction flow if RabbitMQ is down,
        // although in production, you might want this to fail or be queued locally first.
        try {
            $connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'localhost'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'user'),
                env('RABBITMQ_PASSWORD', 'password'),
                env('RABBITMQ_VHOST', '/')
            );
            $channel = $connection->channel();

            $queue = 'transaction_queue';
            $channel->queue_declare($queue, false, true, false, false);

            $msg = new AMQPMessage(
                json_encode($payload),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $channel->basic_publish($msg, '', $queue);

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Failed to publish transaction to RabbitMQ: ' . $e->getMessage());
            // Intentionally not throwing exception here to avoid breaking the create transaction flow.
            // In a real production system with outbox pattern, we would save to local DB table first.
        }
    }
}
