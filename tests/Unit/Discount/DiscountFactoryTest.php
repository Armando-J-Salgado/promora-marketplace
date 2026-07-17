<?php

use App\Discounts\DefaultDiscount;
use App\Discounts\DiscountFactory;
use App\Discounts\FixedDiscount;
use App\Discounts\PercentageDiscount;
use App\Discounts\TieredDiscount;
use App\Models\Order;
use App\Models\Promocode;

it('returns a FixedDiscount instance for fixed promocodes', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->fixed(25.0)->create();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(FixedDiscount::class);
});

it('returns a PercentageDiscount instance for percent promocodes', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->percent(10.0)->create();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(PercentageDiscount::class);
});

it('returns a TieredDiscount instance for tiered promocodes', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->state([
        'type' => 'tiered',
        'value' => 10.0,
    ])->create();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(TieredDiscount::class);
});

it('returns a DefaultDiscount instance for unsupported promocode types', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->state([
        'type' => 'unknown',
        'value' => 10.0,
    ])->create();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(DefaultDiscount::class);
});
