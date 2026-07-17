<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\MaxDiscountValidator;

it('passes when discount is below the max', function () {
    $promocode = tap(new Promocode(), function($p) {
        $p->id = 1;
        $p->rules = ['max_discount_amount' => 50.0];
    });

    $validator = new MaxDiscountValidator(40.0);
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('passes when discount equals the max exactly', function () {
    $promocode = tap(new Promocode(), function($p) {
        $p->id = 1;
        $p->rules = ['max_discount_amount' => 50.0];
    });

    $validator = new MaxDiscountValidator(50.0);
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El monto a descontar sobrepasa el límite del cupón'", function () {
    $promocode = tap(new Promocode(), function($p) {
        $p->id = 1;
        $p->rules = ['max_discount_amount' => 50.0];
    });

    $validator = new MaxDiscountValidator(60.0);
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El monto a descontar sobrepasa el límite del cupón');
});

it("throws exception when 'El monto máximo que se puede descontar no ha sido establecido para el código promocional'", function () {
    $promocode = tap(new Promocode(), function($p) {
        $p->id = 1;
        $p->rules = [];
    });

    $validator = new MaxDiscountValidator(40.0);
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El monto máximo que se puede descontar no ha sido establecido para el código promocional');
});
