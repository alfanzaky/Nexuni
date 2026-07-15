<?php

namespace App\Providers;

use App\Domains\Transaction\Events\TransactionCreatedEvent;
use App\Domains\Transaction\Listeners\PublishTransactionToRabbitMQ;
use App\Domains\Transaction\Services\RabbitMQPublisherService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register RabbitMQ publisher as a singleton so the connection is reused
        // across multiple transactions within the same request/worker lifecycle.
        $this->app->singleton(RabbitMQPublisherService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            TransactionCreatedEvent::class,
            [PublishTransactionToRabbitMQ::class, 'handle']
        );
    }
}
