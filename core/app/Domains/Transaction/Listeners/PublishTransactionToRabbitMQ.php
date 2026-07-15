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

        try {
            $connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost')
            );
            $channel = $connection->channel();

            $queue = config('rabbitmq.queues.transaction');
            $channel->queue_declare($queue, false, true, false, false);

            $msg = new AMQPMessage(
                json_encode($payload),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $channel->basic_publish($msg, '', $queue);

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            \Log::error('Failed to publish transaction to RabbitMQ: '.$e->getMessage());
            // Intentionally not throwing exception here to avoid breaking the create transaction flow.
            // In a real production system with outbox pattern, we would save to local DB table first.
        }
    }
}
