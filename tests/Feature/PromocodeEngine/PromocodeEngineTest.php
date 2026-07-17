<?php

use App\Logger\Logger;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Service;
use App\PromocodeEngine\PromocodeEngine;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;

function buildPromocodeEngine(): PromocodeEngine
{
    return new PromocodeEngine(
        new PromocodeValidationService,
        new PriceCalculatorService,
        Logger::getInstance(),
    );
}

it('validates a promocode that satisfies every configured rule', function () {
    $category = Category::factory()->create();
    $customer = Customer::factory()->create();

    $promocode = Promocode::factory()
        ->withMinPurchase(50.0)
        ->withGlobalUsageLimit(5)
        ->withUserUsageLimit(2)
        ->withGlobalAmountLimit(100.0)
        ->withMaxDiscount(20.0)
        ->withEligibleCategories([$category->id])
        ->firstOrderOnly()
        ->create();

    $promocode->allowedCustomers()->attach($customer->id);

    $service = Service::factory()->create(['price' => 100.0, 'category_id' => $category->id]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    expect(buildPromocodeEngine()->validateCode($order, $promocode))->toBeTrue();
});

it('throws when the promocode does not exist', function () {
    $promocode = Promocode::factory()->create();
    $id = $promocode->id;
    $promocode->delete();

    $stalePromocode = new Promocode;
    $stalePromocode->id = $id;
    $stalePromocode->rules = [];

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $stalePromocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no existe');
});

it('throws when the promocode has expired', function () {
    $promocode = Promocode::factory()->expired()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');
});

it('throws when the promocode is paused', function () {
    $promocode = Promocode::factory()->paused()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('throws when the order subtotal is below the configured minimum purchase amount', function () {
    $promocode = Promocode::factory()->withMinPurchase(500.0)->create();

    $order = Order::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no cumple con el subtotal mínimo necesario');
});

it('throws when the promocode has already reached its global usage limit', function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(1)->create();

    PromocodeRedemption::factory()->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ya ha superado el número máximo de canjes globales');
});

it('throws when the promocode is restricted to customers that do not include the buyer', function () {
    $owner = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true, 'restricted_usage' => true],
    ]);
    $promocode->allowedCustomers()->attach($owner->id);

    $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no ha sido asignado a este usuario');
});

it('logs the applied promocode through the real Logger singleton', function () {
    $reflection = new ReflectionClass(Logger::class);
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setValue(null, null);

    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);
    $order = Order::factory()->create();

    buildPromocodeEngine()->validateCode($order, $promocode);

    $logger = Logger::getInstance();
    $logsProperty = $reflection->getProperty('logs');
    $logsProperty->setAccessible(true);

    expect($logsProperty->getValue($logger))
        ->toContain("Promocode #{$promocode->id} aplicado a orden #{$order->id}. Precio final: 0");
});
