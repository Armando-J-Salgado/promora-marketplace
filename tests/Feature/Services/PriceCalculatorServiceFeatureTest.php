<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Services\PriceCalculatorService;

function priceCalculator(): PriceCalculatorService
{
    return new PriceCalculatorService;
}

it('calculates final price with percent discount (10% of $200 = $20 off)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);

    $promocode = Promocode::factory()->percent(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(180.0);
});

it('calculates final price with percent discount (50% of $100 = $50 off)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(50.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(50.0);
});

it('calculates final price with fixed discount ($10 off $100)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->fixed(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(90.0);
});

it('returns 0 when fixed discount exceeds subtotal', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 5.0]);

    $promocode = Promocode::factory()->fixed(50.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(0.0);
});

it('calculates final price with tiered discount (uses promocode value)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->create([
        'type' => 'tiered',
        'value' => 15.0,
        'rules' => ['validity' => true, 'state' => true],
        'status' => 'active',
        'activation_date' => now()->subDay(),
        'expiration_date' => now()->addMonth(),
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(85.0);
});

it('returns subtotal when promocode type is unknown (DefaultDiscount)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->create([
        'type' => 'unknown_type',
        'value' => 20.0,
        'rules' => ['validity' => true, 'state' => true],
        'status' => 'active',
        'activation_date' => now()->subDay(),
        'expiration_date' => now()->addMonth(),
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(100.0);
});

it('returns subtotal when promocode value is 0 (validate fails)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(0.0)->create([
        'rules' => ['validity' => true, 'state' => true],
        'value' => 0.0,
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(100.0);
});

it('returns 0 when order has no services (subtotal is 0)', function () {
    $customer = Customer::factory()->create();

    $promocode = Promocode::factory()->percent(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $result = priceCalculator()->calculatePrice($order, $promocode);

    expect($result)->toBe(0.0);
});
