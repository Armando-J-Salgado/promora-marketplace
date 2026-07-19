<?php

use App\Logger\Logger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Validations\RestrictedUsageValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when customer is in the allowed customers list', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $promocode = Promocode::factory()->create();
    $promocode->allowedCustomers()->attach($customer->id);

    $validator = new RestrictedUsageValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] RestrictedUsageValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it("throws exception when 'El código promocional no ha sido asignado a este usuario'", function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $promocode = Promocode::factory()->create();

    $validator = new RestrictedUsageValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no ha sido asignado a este usuario');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] RestrictedUsageValidator | code=restricted_usage | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional no ha sido asignado a este usuario");
});
