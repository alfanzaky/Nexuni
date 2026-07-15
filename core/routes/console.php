<?php

use App\Console\Commands\PublishOutboxMessages;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Outbox relay: forward pending messages to RabbitMQ every 30 seconds.
// This ensures transactions are delivered to the Go Engine even during
// transient RabbitMQ downtime windows.
Schedule::command(PublishOutboxMessages::class)->everyThirtySeconds();
