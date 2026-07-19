<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ValidityValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when within the valid period', function () {
    $promocode = new Promocode;
    $promocode->activation_date = now()->subDay();
    $promocode->expiration_date = now()->addDay();
    $order = new Order;

    $validator = new ValidityValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] ValidityValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it('throws when the promocode activation is in the future', function () {
    $promocode = new Promocode;
    $promocode->activation_date = now()->addDays(2);
    $promocode->expiration_date = now()->addDays(5);
    $order = new Order;

    $validator = new ValidityValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aún no comienza su período de canje');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] ValidityValidator | code=expired_coupon | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional aún no comienza su período de canje");
});

it('throws when the promocode has expired', function () {
    $promocode = new Promocode;
    $promocode->activation_date = now()->subDays(5);
    $promocode->expiration_date = now()->subDays(2);
    $order = new Order;

    $validator = new ValidityValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] ValidityValidator | code=expired_coupon | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional ha caducado");
});
