<?php

use App\Models\Order;
use App\Models\Promocode;
use App\PromocodeEngine\PromocodeEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('returns true when promocode is valid', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->create();

    $this->mock(PromocodeEngine::class, function (MockInterface $mock) use ($order, $promocode) {
        $mock->shouldReceive('validateCode')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->is($order)),
                Mockery::on(fn ($arg) => $arg->is($promocode)),
            )
            ->andReturn(true);
    });

    $response = $this->postJson("/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertOk()
        ->assertJson([
            'valid' => true,
        ]);
});

it('returns false when promocode is invalid', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->create();

    $this->mock(PromocodeEngine::class, function (MockInterface $mock) use ($order, $promocode) {
        $mock->shouldReceive('validateCode')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->is($order)),
                Mockery::on(fn ($arg) => $arg->is($promocode)),
            )
            ->andReturn(false);
    });

    $response = $this->postJson("/v1/orders/{$order->id}/promocode/{$promocode->id}");

    $response->assertOk()
        ->assertJson([
            'valid' => false,
        ]);
});

it('returns 404 when order does not exist', function () {
    $promocode = Promocode::factory()->create();

    $response = $this->postJson("/v1/orders/999999/promocode/{$promocode->id}");

    $response->assertNotFound();
});

it('returns 404 when promocode does not exist', function () {
    $order = Order::factory()->create();

    $response = $this->postJson("/v1/orders/{$order->id}/promocode/999999");

    $response->assertNotFound();
});

it('calls promocode engine with correct order and promocode', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->create();

    $this->mock(PromocodeEngine::class, function (MockInterface $mock) use ($order, $promocode) {
        $mock->shouldReceive('validateCode')
            ->once()
            ->withArgs(function (Order $receivedOrder, Promocode $receivedPromocode) use ($order, $promocode) {
                return $receivedOrder->is($order) && $receivedPromocode->is($promocode);
            })
            ->andReturn(true);
    });

    $this->postJson("/v1/orders/{$order->id}/promocode/{$promocode->id}")
        ->assertOk();
});