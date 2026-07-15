<?php

namespace App\Providers;

use App\Domains\Transaction\Events\TransactionCreatedEvent;
use App\Domains\Transaction\Listeners\PublishTransactionToRabbitMQ;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
