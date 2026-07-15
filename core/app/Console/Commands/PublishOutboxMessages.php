<?php

namespace App\Console\Commands;

use App\Domains\Transaction\Models\OutboxMessage;
use App\Domains\Transaction\Services\RabbitMQPublisherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

class PublishOutboxMessages extends Command
{
    protected $signature = 'outbox:publish';

    protected $description = 'Publish pending outbox messages to RabbitMQ';

    private const MAX_FAILED_ATTEMPTS = 5;

    public function __construct(private readonly RabbitMQPublisherService $publisher)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $pending = OutboxMessage::where('status', 'pending')
            ->where('failed_attempts', '<', self::MAX_FAILED_ATTEMPTS)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        if ($pending->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Publishing {$pending->count()} pending outbox message(s)...");

        foreach ($pending as $message) {
            DB::transaction(function () use ($message) {
                // Re-fetch with a row lock to prevent concurrent job runs from double-publishing
                $locked = OutboxMessage::where('id', $message->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return; // Already picked up by another process
                }

                try {
                    $channel = $this->publisher->getChannel();
                    $queue = config('rabbitmq.queues.transaction');
                    $channel->queue_declare($queue, false, true, false, false);

                    $msg = new AMQPMessage(
                        json_encode($locked->payload),
                        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
                    );
                    $channel->basic_publish($msg, '', $queue);

                    $locked->update([
                        'status' => 'sent',
                        'published_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    $newAttempts = $locked->failed_attempts + 1;
                    $status = $newAttempts >= self::MAX_FAILED_ATTEMPTS ? 'failed' : 'pending';

                    $locked->update([
                        'status' => $status,
                        'failed_attempts' => $newAttempts,
                    ]);

                    Log::error('Outbox publish failed.', [
                        'outbox_id' => $locked->id,
                        'attempts' => $newAttempts,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }

        return Command::SUCCESS;
    }
}
