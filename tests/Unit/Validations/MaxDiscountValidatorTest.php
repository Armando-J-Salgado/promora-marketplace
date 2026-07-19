<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Validations\MaxDiscountValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when discount is below the max', function () {
    $promocode = tap(new Promocode, function ($p) {
        $p->id = 1;
        $p->rules = ['max_discount_amount' => 50.0];
    });

    $order = new Order;
    $validator = new MaxDiscountValidator(40.0);
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] MaxDiscountValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it('passes when discount equals the max exactly', function () {
    $promocode = tap(new Promocode, function ($p) {
        $p->id = 1;
        $p->rules = ['max_discount_amount' => 50.0];
    });

    $validator = new MaxDiscountValidator(50.0);
    expect(fn () => $validator->handle(new Order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El monto a descontar sobrepasa el límite del cupón'", function () {
    $promocode = tap(new Promocode, function ($p) {
        $p->id = 1;
        $p->rules = ['max_discount_amount' => 50.0];
    });

    $order = new Order;
    $validator = new MaxDiscountValidator(60.0);
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El monto a descontar sobrepasa el límite del cupón');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] MaxDiscountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->id} | El monto a descontar sobrepasa el límite del cupón");
});

it("throws exception when 'El monto máximo que se puede descontar no ha sido establecido para el código promocional'", function () {
    $promocode = tap(new Promocode, function ($p) {
        $p->id = 1;
        $p->rules = [];
    });

    $order = new Order;
    $validator = new MaxDiscountValidator(40.0);
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El monto máximo que se puede descontar no ha sido establecido para el código promocional');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] MaxDiscountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->id} | El monto máximo que se puede descontar no ha sido establecido para el código promocional");
});
