<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\MaxDiscountValidator;

it('passes when discount is below the max', function () {
    $promocode = Promocode::factory()->withMaxDiscount(50.0)->create();
    $order = Order::factory()->create();

    $validator = new MaxDiscountValidator(40.0);
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El monto a descontar sobrepasa el límite del cupón'", function () {
    $promocode = Promocode::factory()->withMaxDiscount(50.0)->create();
    $order = Order::factory()->create();

    $validator = new MaxDiscountValidator(60.0);
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El monto a descontar sobrepasa el límite del cupón');
});

it("throws exception when 'El monto máximo que se puede descontar no ha sido establecido para el código promocional'", function () {
    $promocode = Promocode::factory()->create();
    $order = Order::factory()->create();

    $validator = new MaxDiscountValidator(40.0);
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El monto máximo que se puede descontar no ha sido establecido para el código promocional');
});
