<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Models\Customer;
use App\Validations\RestrictedUsageValidator;

it('passes when customer is in the allowed customers list', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    
    $promocode = Promocode::factory()->create();
    $promocode->allowedCustomers()->attach($customer->id);

    $validator = new RestrictedUsageValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El código promocional no ha sido asignado a este usuario'", function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    
    $promocode = Promocode::factory()->create();

    $validator = new RestrictedUsageValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no ha sido asignado a este usuario');
});
