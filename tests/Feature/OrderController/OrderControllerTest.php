<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Service;

it('returns valid true for a percent promocode that satisfies all configured rules', function () {
    $category = Category::factory()->create();
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0, 'category_id' => $category->id]);

    $promocode = Promocode::factory()
        ->percent(15.0)
        ->withMinPurchase(50.0)
        ->withEligibleCategories([$category->id])
        ->withGlobalUsageLimit(5)
        ->withUserUsageLimit(2)
        ->create();

    $promocode->allowedCustomers()->attach($customer->id);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertOk()
        ->assertJson(['valid' => true]);
});

it('returns valid true for a fixed promocode', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->fixed(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertOk()
        ->assertJson(['valid' => true]);
});

it('returns valid true for a promocode with only base rules (no configurable rules)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 50.0]);

    $promocode = Promocode::factory()->percent(10.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertOk()
        ->assertJson(['valid' => true]);
});

it('returns 404 when the promocode does not exist', function () {
    $order = Order::factory()->create();

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/99999");

    $response->assertNotFound();
});

it('returns 422 when the promocode has expired', function () {
    $promocode = Promocode::factory()->expired()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when the promocode is paused', function () {
    $promocode = Promocode::factory()->paused()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when the promocode is in draft state', function () {
    $promocode = Promocode::factory()->draft()->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create();

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when the order subtotal is below the min_purchase_amount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 20.0]);

    $promocode = Promocode::factory()->withMinPurchase(500.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when the global usage limit has been reached', function () {
    $promocode = Promocode::factory()->withGlobalUsageLimit(1)->create();

    PromocodeRedemption::factory()->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create();

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when the promocode is restricted and user is not allowed', function () {
    $owner = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true, 'restricted_usage' => true],
    ]);
    $promocode->allowedCustomers()->attach($owner->id);

    $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when the category is not eligible for the promocode', function () {
    $allowedCategory = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $customer = Customer::factory()->create();

    $service = Service::factory()->create(['price' => 100.0, 'category_id' => $otherCategory->id]);

    $promocode = Promocode::factory()
        ->withEligibleCategories([$allowedCategory->id])
        ->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});

it('returns 422 when first_order_only and buyer already has order history', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    Order::factory()->create(['customer_id' => $customer->id, 'status' => 'paid']);

    $promocode = Promocode::factory()
        ->firstOrderOnly()
        ->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $response = $this->postJson("/api/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertUnprocessable()
        ->assertJson(['valid' => false]);
});
