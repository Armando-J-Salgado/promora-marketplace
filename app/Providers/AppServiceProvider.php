<?php

namespace App\Providers;

use App\Logger\Logger;
use App\PromocodeEngine\PromocodeEngine;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Logger::class, fn () => Logger::getInstance());

        $this->app->singleton(PromocodeEngine::class, function ($app) {
            return new PromocodeEngine(
                $app->make(PromocodeValidationService::class),
                $app->make(PriceCalculatorService::class),
                $app->make(Logger::class),
            );
        });
    }

    public function boot(): void {}
}
