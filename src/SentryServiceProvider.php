<?php

namespace Doitonlinemedia\Sentry;

use Illuminate\Support\ServiceProvider;

class SentryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/sentry.php' => config_path('sentry.php'),
        ], 'sentry-api');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
