<?php

namespace App\Domains\Transaction\Services;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQPublisherService
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    public function getChannel(): AMQPChannel
    {
        if ($this->isConnected()) {
            return $this->channel;
        }

        // Clean up any stale/broken connection resources before creating new ones.
        // Without this, overwriting $this->connection would leak the old socket.
        $this->close();

        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );

        $this->channel = $this->connection->channel();

        return $this->channel;
    }

    private function isConnected(): bool
    {
        return $this->connection !== null
            && $this->connection->isConnected()
            && $this->channel !== null
            && $this->channel->is_open();
    }

    public function close(): void
    {
        // Null properties BEFORE calling close() so that:
        // 1. A failed channel close doesn't prevent the connection close from running.
        // 2. Subsequent calls to isConnected() immediately return false, preventing
        //    any further use of the broken resources.
        $channel = $this->channel;
        $connection = $this->connection;
        $this->channel = null;
        $this->connection = null;

        try {
            $channel?->close();
        } catch (\Throwable) {
            // Swallow — socket may already be closed if broker restarted.
        }

        try {
            $connection?->close();
        } catch (\Throwable) {
            // Swallow — same reason as above.
        }
    }

    public function __destruct()
    {
        // Exceptions thrown inside __destruct() cause a PHP fatal error.
        // close() already guards each resource with try-catch, so this is safe.
        $this->close();
    }
}
