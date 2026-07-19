<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Validations\StateValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when the status is active', function () {
    $promocode = new Promocode;
    $promocode->status = 'active';
    $order = new Order;

    $validator = new StateValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] StateValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it('throws when the status is draft', function () {
    $promocode = new Promocode;
    $promocode->status = 'draft';
    $order = new Order;

    $validator = new StateValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] StateValidator | code=invalid_code | promocode=#{$promocode->id} | order=#{$order->id} | El código no se encuentra activo");
});

it('throws when the status is paused', function () {
    $promocode = new Promocode;
    $promocode->status = 'paused';

    $validator = new StateValidator;
    expect(fn () => $validator->handle(new Order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('throws when the status is expired', function () {
    $promocode = new Promocode;
    $promocode->status = 'expired';

    $validator = new StateValidator;
    expect(fn () => $validator->handle(new Order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});
