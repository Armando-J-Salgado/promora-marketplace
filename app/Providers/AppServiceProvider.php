<?php

namespace App\Providers;

use App\Logger\Logger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Logger::class, fn () => Logger::getInstance());
    }

    public function boot(): void {}
}
