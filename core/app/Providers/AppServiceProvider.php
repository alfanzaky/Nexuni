<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\Event::listen(
            \App\Domains\Transaction\Events\TransactionCreatedEvent::class,
            [\App\Domains\Transaction\Listeners\PublishTransactionToRabbitMQ::class, 'handle']
        );
    }
}
