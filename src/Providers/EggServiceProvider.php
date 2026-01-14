<?php

namespace G4\Egg\Providers;

use G4\Egg\Handlers\EggExceptionHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class EggServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (app()->runningInConsole()) {
            \Log::info("Egg-package booted!");
        }

        $this->publishes([
            __DIR__ . '/../../config/egg.php' => config_path('egg.php'),
        ], 'egg-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/egg.php',
            'egg'
        );

        $this->commands([
            \G4\Egg\Console\InstallCommand::class,
        ]);

        // Override the default exception handler
        $this->app->singleton(
            ExceptionHandler::class,
            EggExceptionHandler::class
        );
    }
}
