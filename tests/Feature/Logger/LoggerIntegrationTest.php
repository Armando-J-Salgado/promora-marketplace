<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\PromocodeEngine\PromocodeEngine;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

test('el logger registra cuando una validacion es exitosa', function () {
    $logger = app(Logger::class);

    $order = Mockery::mock(Order::class);
    $promocode = Mockery::mock(Promocode::class);
    $validationService = Mockery::mock(PromocodeValidationService::class);
    $calculatorService = Mockery::mock(PriceCalculatorService::class);

    $order->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $order->shouldReceive('getId')->andReturn(1);
    $promocode->shouldReceive('getAttribute')->with('id')->andReturn('PROMO10');
    $validationService->shouldReceive('validate')->once()->andReturn(true);
    $calculatorService->shouldReceive('calculatePrice')->once()->andReturn(10.0);

    $engine = new PromocodeEngine($validationService, $calculatorService, $logger);
    $engine->validateCode($order, $promocode);

    $reflection = new ReflectionClass($logger);
    $logs = $reflection->getProperty('logs');
    $logs->setAccessible(true);

    expect($logs->getValue($logger))->not->toBeEmpty();
});

test('el logger registra cuando una validacion falla', function () {
    $logger = app(Logger::class);

    $order = Mockery::mock(Order::class);
    $promocode = Mockery::mock(Promocode::class);
    $validationService = Mockery::mock(PromocodeValidationService::class);
    $calculatorService = Mockery::mock(PriceCalculatorService::class);

    $order->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $order->shouldReceive('getId')->andReturn(1);
    $promocode->shouldReceive('getAttribute')->with('id')->andReturn('PROMO10');
    $validationService->shouldReceive('validate')->once()->andReturn(false);
    $calculatorService->shouldReceive('calculatePrice')->never();

    $engine = new PromocodeEngine($validationService, $calculatorService, $logger);
    $engine->validateCode($order, $promocode);

    $reflection = new ReflectionClass($logger);
    $logs = $reflection->getProperty('logs');
    $logs->setAccessible(true);

    expect($logs->getValue($logger))->not->toBeEmpty();
});

test('el logger inyectado en el engine es la instancia singleton', function () {
    $loggerFromContainer = app(Logger::class);
    $loggerFromSingleton = Logger::getInstance();

    $validationService = Mockery::mock(PromocodeValidationService::class);
    $calculatorService = Mockery::mock(PriceCalculatorService::class);

    $engine = new PromocodeEngine($validationService, $calculatorService, $loggerFromContainer);

    expect($loggerFromContainer)->toBe($loggerFromSingleton);
});
