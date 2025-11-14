<?php

namespace Kaspe\Egg\Providers;

use Illuminate\Support\ServiceProvider;

class EggServiceProvider extends ServiceProvider
{
    public function boot()
    {
        dump("Egg-package booted!");
    }
}