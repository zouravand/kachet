<?php

namespace Tedon\Kachet;

use Illuminate\Support\ServiceProvider;

class KachetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/kachet.php', 'kachet'
        );

        // Register the main class as a singleton
        $this->app->singleton('kachet', function ($app) {
            return new Kachet(config('kachet'));
        });
    }

    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kachet.php' => config_path('kachet.php'),
            ], 'config');
        }
    }
}