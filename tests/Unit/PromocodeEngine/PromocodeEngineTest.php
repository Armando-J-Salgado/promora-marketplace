<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\PromocodeEngine\PromocodeEngine;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;

function promocodeEngineUnderTest(): PromocodeEngine
{
    return new PromocodeEngine(
        new PromocodeValidationService,
        new PriceCalculatorService,
        Logger::getInstance(),
    );
}

it('returns true when the promocode is valid', function () {
    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(promocodeEngineUnderTest()->validateCode($order, $promocode))->toBeTrue();
});

it('propagates the exception when the promocode is paused', function () {
    $promocode = Promocode::factory()->paused()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => promocodeEngineUnderTest()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('propagates the exception when the promocode has expired', function () {
    $promocode = Promocode::factory()->expired()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => promocodeEngineUnderTest()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');
});

it('propagates the exception when the promocode does not exist', function () {
    $promocode = Promocode::factory()->create();
    $id = $promocode->id;
    $promocode->delete();

    $stalePromocode = new Promocode;
    $stalePromocode->id = $id;
    $stalePromocode->rules = [];

    $order = Order::factory()->create();

    expect(fn () => promocodeEngineUnderTest()->validateCode($order, $stalePromocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no existe');
});

it('logs the success message through the real Logger singleton', function () {
    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);
    $order = Order::factory()->create();

    promocodeEngineUnderTest()->validateCode($order, $promocode);

    expect(Logger::getInstance()->getLogs())
        ->toContain("Promocode #{$promocode->id} aplicado a orden #{$order->id}. Precio final: 0");
});
