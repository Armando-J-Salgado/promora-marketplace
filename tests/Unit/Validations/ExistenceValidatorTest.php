<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ExistenceValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when the promocode exists', function () {
    $promocode = Promocode::factory()->create();
    $order = Order::factory()->create();
    $stubPromocode = tap(new Promocode, fn ($p) => $p->id = $promocode->id);
    $validator = new ExistenceValidator;

    expect(fn () => $validator->handle($order, $stubPromocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] ExistenceValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it('throws when the promocode does not exist', function () {
    $order = Order::factory()->create();
    $stubPromocode = tap(new Promocode, fn ($p) => $p->id = 9999);
    $validator = new ExistenceValidator;

    expect(fn () => $validator->handle($order, $stubPromocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no existe');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] ExistenceValidator | code=invalid_code | promocode=#9999 | order=#{$order->id} | El código promocional no existe");
});
