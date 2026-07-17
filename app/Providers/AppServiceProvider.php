<?php

namespace App\Providers;

use App\Logger\Logger;
use App\PromocodeEngine\PromocodeEngine;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PromocodeEngine::class, function ($app) {
            return new PromocodeEngine(
                $app->make(PromocodeValidationService::class),
                $app->make(PriceCalculatorService::class),
                $app->make(Logger::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
