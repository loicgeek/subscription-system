<?php 

namespace NtechServices\SubscriptionSystem;

use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register the command
        if ($this->app->runningInConsole()) {
            $this->commands([
                \NtechServices\SubscriptionSystem\Console\Commands\MigrateSubscriptionSystem::class,
            ]);
        }

        // Load migrations
       // $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Load routes (if needed)
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Optionally publish config or views here

        $this->publishes([
            __DIR__.'/Config/subscription.php' => config_path('subscription.php'),
        ]);
    }

    public function register()
    {
        // Register package services if needed
        $this->mergeConfigFrom(
            __DIR__.'/Config/subscription.php', 'subscription'
        );
    }
}
