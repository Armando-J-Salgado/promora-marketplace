<?php

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Validations\GlobalUsageValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when redemption count is below the limit', function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(5)->create();

    PromocodeRedemption::factory()->count(2)->create([
        'promocode_id' => $promocode->id,
    ]);

    $order = new Order;

    $validator = new GlobalUsageValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] GlobalUsageValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it("throws exception when 'El código promocional ya ha superado el número máximo de canjes globales'", function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(5)->create();

    PromocodeRedemption::factory()->count(5)->create([
        'promocode_id' => $promocode->id,
    ]);

    $order = new Order;

    $validator = new GlobalUsageValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ya ha superado el número máximo de canjes globales');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] GlobalUsageValidator | code=usage_limit_reached | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional ya ha superado el número máximo de canjes globales");
});

it("throws exception when 'El máximo global no ha sido definido para este cupón'", function () {
    $promocode = tap(new Promocode, function ($p) {
        $p->id = 1;
        $p->rules = [];
    });
    $order = new Order;

    $validator = new GlobalUsageValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El máximo global no ha sido definido para este cupón');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] GlobalUsageValidator | code=usage_limit_reached | promocode=#{$promocode->id} | order=#{$order->id} | El máximo global no ha sido definido para este cupón");
});
