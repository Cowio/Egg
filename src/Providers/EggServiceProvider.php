<?php

namespace G4\Egg\Providers;

use Illuminate\Support\ServiceProvider;

class EggServiceProvider extends ServiceProvider
{
    public function boot()
    {
        dump("Egg-package booted!");

        // Publish config file
        $this->publishes([
            __DIR__ . '/../../config/egg.php' => config_path('egg.php'),
        ], 'egg-config');
    }

    public function register()
    {
        // Merge the package config with the application's copy
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/egg.php',
            'egg'
        );

        // Register commands
        $this->commands([
            \G4\Egg\Console\InstallCommand::class,
        ]);
    }
}