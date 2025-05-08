<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public final function register() : void
    {
        $this->app->singleton(Messaging::class, function ($app) {
            $factory = (new Factory)
                ->withServiceAccount(config('firebase.credentials'));
            return $factory->createMessaging();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public final function boot(): void
    {
        //
    }
}
