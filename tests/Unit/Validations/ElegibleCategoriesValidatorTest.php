<?php

use App\Logger\Logger;
use App\Models\Category;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Validations\ElegibleCategoriesValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when order contains a directly listed eligible category', function () {
    $categoryA = Category::factory()->create();

    $promocode = Promocode::factory()->withEligibleCategories([$categoryA->id])->create();

    $service = Service::factory()->create(['category_id' => $categoryA->id]);
    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);

    $validator = new ElegibleCategoriesValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] ElegibleCategoriesValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it('passes when order contains a child of an eligible category', function () {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->withParent($categoryA)->create();

    $promocode = Promocode::factory()->withEligibleCategories([$categoryA->id])->create();

    $service = Service::factory()->create(['category_id' => $categoryB->id]);
    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);

    $validator = new ElegibleCategoriesValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('passes when order contains the parent of an eligible category', function () {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->withParent($categoryA)->create();

    $promocode = Promocode::factory()->withEligibleCategories([$categoryB->id])->create();

    $service = Service::factory()->create(['category_id' => $categoryA->id]);
    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);

    $validator = new ElegibleCategoriesValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'La orden no contiene ninguna categoría elegible para este código promocional'", function () {
    $categoryA = Category::factory()->create();
    $categoryC = Category::factory()->create(); // Unrelated

    $promocode = Promocode::factory()->withEligibleCategories([$categoryA->id])->create();

    $service = Service::factory()->create(['category_id' => $categoryC->id]);
    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);

    $validator = new ElegibleCategoriesValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no contiene ninguna categoría elegible para este código promocional');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] ElegibleCategoriesValidator | code=invalid_code | promocode=#{$promocode->id} | order=#{$order->id} | La orden no contiene ninguna categoría elegible para este código promocional");
});
