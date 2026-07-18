<?php

use App\Discounts\TieredDiscount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Models\Tier;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Construye un Order real con su Customer, tramos y servicio adjunto,
 * listo para ejercitar TieredDiscount.
 */

function buildTieredScenario(
    int $historicalPaidOrders = 0,
    array $tiers = [['minimum_orders' => 0, 'discount_value' => 5.0]],
    float $servicePrice = 100.0,
    array $rules = []
): array {
    $customer = Customer::factory()->create();

    Order::factory()->count($historicalPaidOrders)->create([
        'customer_id' => $customer->id,
        'status'      => 'paid',
        'subtotal'    => 50.0,
        'total'       => 50.0,
    ]);

    $promocode = Promocode::factory()->tiered()->create(['rules' => $rules]);

    foreach ($tiers as $tierData) {
        Tier::factory()->withMinOrders($tierData['minimum_orders'], $tierData['discount_value'])->create([
            'promocode_id' => $promocode->id,
        ]);
    }

    $service = Service::factory()->create(['price' => $servicePrice]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => 'pending',
        'subtotal'    => $servicePrice,
        'total'       => $servicePrice,
    ]);

    $order->services()->attach($service->id, ['quantity' => 1]);

    return [$order, $promocode];
}

/**
 * Crea una orden con un servicio adjunto para que getSubtotal() funcione.
 */
function orderWithService(Customer $customer, string $status, float $price): Order
{
    $service = Service::factory()->create(['price' => $price]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => $status,
        'subtotal'    => $price,
        'total'       => $price,
    ]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    return $order;
}

it('aplica el tramo base cuando el comprador no tiene historial de órdenes', function () {
    [$order, $promocode] = buildTieredScenario(historicalPaidOrders: 0);

    $discount = new TieredDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(5.0);
});

it('aplica el tramo intermedio cuando el comprador tiene 3 órdenes previas', function () {
    [$order, $promocode] = buildTieredScenario(
        historicalPaidOrders: 3,
        tiers: [
            ['minimum_orders' => 0,  'discount_value' => 5.0],
            ['minimum_orders' => 3,  'discount_value' => 10.0],
            ['minimum_orders' => 10, 'discount_value' => 15.0],
        ]
    );

    $discount = new TieredDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(10.0);
});

it('aplica el tramo máximo cuando el comprador tiene 10 o más órdenes previas', function () {
    [$order, $promocode] = buildTieredScenario(
        historicalPaidOrders: 10,
        tiers: [
            ['minimum_orders' => 0,  'discount_value' => 5.0],
            ['minimum_orders' => 3,  'discount_value' => 10.0],
            ['minimum_orders' => 10, 'discount_value' => 15.0],
        ]
    );

    $discount = new TieredDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(15.0);
});

it('excluye órdenes canceladas del conteo histórico', function () {
    $customer = Customer::factory()->create();

    Order::factory()->count(5)->create(['customer_id' => $customer->id, 'status' => 'paid',      'subtotal' => 50.0, 'total' => 50.0]);
    Order::factory()->count(3)->create(['customer_id' => $customer->id, 'status' => 'cancelled', 'subtotal' => 50.0, 'total' => 50.0]);

    $promocode = Promocode::factory()->tiered()->create(['rules' => []]);
    Tier::factory()->withMinOrders(0,  5.0)->create(['promocode_id' => $promocode->id]);
    Tier::factory()->withMinOrders(3,  10.0)->create(['promocode_id' => $promocode->id]);
    Tier::factory()->withMinOrders(10, 15.0)->create(['promocode_id' => $promocode->id]);

    $order = orderWithService($customer, 'pending', 100.0);

    $discount = new TieredDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(10.0);
});

it('excluye órdenes en borrador del conteo histórico', function () {
    $customer = Customer::factory()->create();

    Order::factory()->count(2)->create(['customer_id' => $customer->id, 'status' => 'paid',  'subtotal' => 50.0, 'total' => 50.0]);
    Order::factory()->count(5)->create(['customer_id' => $customer->id, 'status' => 'draft', 'subtotal' => 50.0, 'total' => 50.0]);

    $promocode = Promocode::factory()->tiered()->create(['rules' => []]);
    Tier::factory()->withMinOrders(0, 5.0)->create(['promocode_id' => $promocode->id]);
    Tier::factory()->withMinOrders(3, 10.0)->create(['promocode_id' => $promocode->id]);

    $order = orderWithService($customer, 'pending', 100.0);

    $discount = new TieredDiscount($order, $promocode);


    expect($discount->calculatePrice())->toBe(5.0);
});

it('excluye currentOrders del conteo para evitar falsos positivos', function () {
    $customer = Customer::factory()->create();

    Order::factory()->count(2)->create(['customer_id' => $customer->id, 'status' => 'paid', 'subtotal' => 50.0, 'total' => 50.0]);

    $promocode = Promocode::factory()->tiered()->create(['rules' => []]);
    Tier::factory()->withMinOrders(0, 5.0)->create(['promocode_id' => $promocode->id]);
    Tier::factory()->withMinOrders(3, 10.0)->create(['promocode_id' => $promocode->id]);

    // La orden actual (pending) no debe contarse en el historial
    $currentOrder = orderWithService($customer, 'pending', 100.0);

    $discount = new TieredDiscount($currentOrder, $promocode);

    // 2 órdenes válidas → tramo base (5%), la orden actual se excluye vía currentOrders
    expect($discount->calculatePrice())->toBe(5.0);
});

it('retorna 0.0 cuando el promocode no tiene tramos definidos', function () {
    [$order, $promocode] = buildTieredScenario(historicalPaidOrders: 5, tiers: []);

    $discount = new TieredDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});
