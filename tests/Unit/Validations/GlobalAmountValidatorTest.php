<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Validations\GlobalAmountValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when total discounted plus current discount is below the limit', function () {
    $promocode = Promocode::factory()->withGlobalAmountLimit(100.0)->create();

    PromocodeRedemption::factory()->create([
        'promocode_id' => $promocode->id,
        'discount_amount' => 50.0,
    ]);

    $order = new Order;
    $validator = new GlobalAmountValidator(30.0);
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] GlobalAmountValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it('passes when total discounted plus current discount equals the limit exactly', function () {
    $promocode = Promocode::factory()->withGlobalAmountLimit(100.0)->create();

    PromocodeRedemption::factory()->create([
        'promocode_id' => $promocode->id,
        'discount_amount' => 70.0,
    ]);

    $validator = new GlobalAmountValidator(30.0);
    expect(fn () => $validator->handle(new Order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El código promocional supera su presupuesto máximo de descuentos'", function () {
    $promocode = Promocode::factory()->withGlobalAmountLimit(100.0)->create();

    PromocodeRedemption::factory()->create([
        'promocode_id' => $promocode->id,
        'discount_amount' => 80.0,
    ]);

    $order = new Order;
    $validator = new GlobalAmountValidator(30.0);
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional supera su presupuesto máximo de descuentos');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] GlobalAmountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional supera su presupuesto máximo de descuentos");
});

it("throws exception when 'El cupón no tiene configurado la cantidad límite global'", function () {
    $promocode = tap(new Promocode, function ($p) {
        $p->id = 1;
        $p->rules = [];
    });

    $order = new Order;
    $validator = new GlobalAmountValidator(30.0);
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El cupón no tiene configurado la cantidad límite global');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] GlobalAmountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->id} | El cupón no tiene configurado la cantidad límite global");
});
