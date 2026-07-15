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
        if ($this->channel !== null) {
            $this->channel->close();
            $this->channel = null;
        }

        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
