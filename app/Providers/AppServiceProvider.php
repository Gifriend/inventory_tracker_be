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
        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);

        $events->subscribe(\App\Listeners\LoanEventLogger::class);
        $events->subscribe(\App\Listeners\LabRequestEventLogger::class);
    }
}
