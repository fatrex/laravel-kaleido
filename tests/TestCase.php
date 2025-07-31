<?php

namespace Fatrex\LaravelKaleido\Tests;

use Fatrex\LaravelKaleido\KaleidoServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            KaleidoServiceProvider::class,
        ];
    }
}
