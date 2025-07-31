<?php

namespace Fatrex\LaravelKaleido;

use Fatrex\LaravelKaleido\Console\Commands\KaleidoDump;
use Fatrex\LaravelKaleido\Console\Commands\KaleidoSync;
use Illuminate\Support\ServiceProvider;

class KaleidoServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                KaleidoDump::class,
                KaleidoSync::class,
            ]);
        }
    }
}
