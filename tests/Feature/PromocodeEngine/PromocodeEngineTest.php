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
        ->percent(15.0)
        ->withMinPurchase(50.0)
        ->withGlobalUsageLimit(5)
        ->withUserUsageLimit(2)
        ->withEligibleCategories([$category->id])
        ->create();

    $promocode->allowedCustomers()->attach($customer->id);

    $service = Service::factory()->create(['price' => 100.0, 'category_id' => $category->id]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    expect(buildPromocodeEngine()->validateCode($order, $promocode))->toBeTrue();
});

it('validates a promocode with only base rules (no configurable rules)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 80.0]);

    $promocode = Promocode::factory()->fixed(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

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

it('throws when the promocode has expired (past expiration date)', function () {
    $promocode = Promocode::factory()->expired()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');
});

it('throws when the promocode is not yet active (future activation date)', function () {
    $promocode = Promocode::factory()->notYetActive()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when the promocode is paused', function () {
    $promocode = Promocode::factory()->paused()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('throws when the promocode is in draft state', function () {
    $promocode = Promocode::factory()->draft()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('throws when the order subtotal is below the configured minimum purchase amount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 20.0]);

    $promocode = Promocode::factory()->withMinPurchase(500.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no cumple con el subtotal mínimo necesario');
});

it('throws when the global usage limit has been reached', function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(1)->create();

    PromocodeRedemption::factory()->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ya ha superado el número máximo de canjes globales');
});

it('throws when the user usage limit has been reached', function () {
    $customer = Customer::factory()->create();

    $promocode = Promocode::factory()->withUserUsageLimit(1)->create();

    // Crear una redención previa del mismo customer
    $previousOrder = Order::factory()->create(['customer_id' => $customer->id]);
    PromocodeRedemption::factory()->create([
        'promocode_id' => $promocode->id,
        'order_id' => $previousOrder->id,
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El usuario ha excedido el número máximo de usos permitidos para este código promocional');
});

it('throws when the order category is not eligible for the promocode', function () {
    $allowedCategory = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $customer = Customer::factory()->create();

    $service = Service::factory()->create(['price' => 100.0, 'category_id' => $otherCategory->id]);

    $promocode = Promocode::factory()
        ->withEligibleCategories([$allowedCategory->id])
        ->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no contiene ninguna categoría elegible para este código promocional');
});

it('throws when first_order_only and buyer already has order history', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    // Crear una orden previa pagada
    Order::factory()->create(['customer_id' => $customer->id, 'status' => 'paid']);

    $promocode = Promocode::factory()->firstOrderOnly()->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    expect(fn () => buildPromocodeEngine()->validateCode($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aplica solo para la primera orden del cliente');
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

it('logs the final price message through the Logger singleton on success', function () {
    // Reset singleton
    $reflection = new ReflectionClass(Logger::class);
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setValue(null, null);

    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    buildPromocodeEngine()->validateCode($order, $promocode);

    $logger = Logger::getInstance();
    $logsProperty = $reflection->getProperty('logs');
    $logsProperty->setAccessible(true);

    $logs = $logsProperty->getValue($logger);

    expect($logs)->toContain("Promocode #{$promocode->id} aplicado a orden #{$order->id}. Precio final: 90");
});
