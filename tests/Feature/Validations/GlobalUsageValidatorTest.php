<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Validations\GlobalUsageValidator;

it('passes when global redemptions are below the limit', function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(5)->create();
    
    PromocodeRedemption::factory()->count(3)->create([
        'promocode_id' => $promocode->id
    ]);

    $order = Order::factory()->create();

    $validator = new GlobalUsageValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El código promocional ya ha superado el número máximo de canjes globales'", function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(3)->create();
    
    PromocodeRedemption::factory()->count(3)->create([
        'promocode_id' => $promocode->id
    ]);

    $order = Order::factory()->create();

    $validator = new GlobalUsageValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ya ha superado el número máximo de canjes globales');
});

it("throws exception when 'El máximo global no ha sido definido para este cupón'", function () {
    $promocode = Promocode::factory()->create();
    
    $order = Order::factory()->create();

    $validator = new GlobalUsageValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El máximo global no ha sido definido para este cupón');
});
