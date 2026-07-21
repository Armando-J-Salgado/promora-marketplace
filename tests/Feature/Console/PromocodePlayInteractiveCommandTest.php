<?php

use App\Logger\Logger;
use App\Models\Category;
use App\Models\Order;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('builds an order with the exact subtotal from multiple services', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'fixed')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '2')
        ->expectsQuestion('Precio del servicio #1', '30')
        ->expectsQuestion('Cantidad del servicio #1', '2')
        ->expectsQuestion('Precio del servicio #2', '15')
        ->expectsQuestion('Cantidad del servicio #2', '1')
        ->expectsQuestion('Categoría del servicio #2', '__new')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->assertExitCode(0);

    $order = Order::query()->latest('id')->first();
    expect((float) $order->subtotal)->toBe(75.0);
});

it('crea una categoría hija de una ya existente vía el flujo __child', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'fixed')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '2')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('Precio del servicio #2', '50')
        ->expectsQuestion('Cantidad del servicio #2', '1')
        ->expectsQuestion('Categoría del servicio #2', '__child')
        ->expectsQuestion('Categoría padre', '1')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->assertExitCode(0);

    $root = Category::query()->oldest('id')->first();
    $child = Category::query()->latest('id')->first();

    expect($child->category_id)->toBe($root->id);
});

it('reparenta una categoría existente vía el flujo __parent', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'fixed')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '2')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('Precio del servicio #2', '50')
        ->expectsQuestion('Cantidad del servicio #2', '1')
        ->expectsQuestion('Categoría del servicio #2', '__parent')
        ->expectsQuestion('Categoría que pasará a ser hija', '1')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->assertExitCode(0);

    $original = Category::query()->oldest('id')->first();
    $newParent = Category::query()->latest('id')->first();

    expect($original->fresh()->category_id)->toBe($newParent->id);
});

it('bloquea con el mensaje exacto cuando no se elige ninguna categoría elegible', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'fixed')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', 'elegible_categories')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '1')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('Categorías elegibles para este código (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->expectsOutputToContain('BLOQUEADO — La orden no contiene ninguna categoría elegible para este código promocional')
        ->assertExitCode(0);
});

it('permite cuando la categoría del servicio se marca como elegible', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'fixed')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', 'elegible_categories')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '1')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('Categorías elegibles para este código (elige una para activarla/desactivarla)', '1')
        ->expectsQuestion('Categorías elegibles para este código (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->expectsOutputToContain('VÁLIDO')
        ->assertExitCode(0);
});

it('aplica el tramo tiered cuando el historial cae exacto en el boundary', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'tiered')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '1')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('¿Cuántos tramos (tiers) tendrá este código?', '2')
        ->expectsQuestion('Tramo #1 — mínimo de órdenes históricas', '0')
        ->expectsQuestion('Tramo #1 — porcentaje de descuento', '10')
        ->expectsQuestion('Tramo #2 — mínimo de órdenes históricas', '3')
        ->expectsQuestion('Tramo #2 — porcentaje de descuento', '20')
        ->expectsQuestion('Órdenes históricas del cliente (no canceladas/borrador) a simular para este escenario', '3')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->expectsOutputToContain('Tramo aplicado: minimum_orders=3 | discount_value=20%')
        ->expectsOutputToContain('Precio final (hipotético si la orden bloqueó): 80')
        ->assertExitCode(0);
});

it('no aplica ningún tramo cuando el historial no alcanza a ninguno', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'tiered')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '1')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('¿Cuántos tramos (tiers) tendrá este código?', '1')
        ->expectsQuestion('Tramo #1 — mínimo de órdenes históricas', '5')
        ->expectsQuestion('Tramo #1 — porcentaje de descuento', '10')
        ->expectsQuestion('Órdenes históricas del cliente (no canceladas/borrador) a simular para este escenario', '0')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->expectsOutputToContain('Tramo aplicado: ninguno (0% de descuento)')
        ->assertExitCode(0);
});

it('cappea el descuento en max_discount_amount y lo refleja en el desglose', function () {
    $this->artisan('promocode:play')
        ->expectsQuestion('Tipo de código', 'fixed')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', 'max_discount_amount')
        ->expectsQuestion('Reglas configurables a activar (elige una para activarla/desactivarla)', '__continue')
        ->expectsConfirmation('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', 'no')
        ->expectsQuestion('¿Cuántos servicios tendrá esta orden?', '1')
        ->expectsQuestion('Precio del servicio #1', '100')
        ->expectsQuestion('Cantidad del servicio #1', '1')
        ->expectsQuestion('Descuento máximo', '1')
        ->expectsConfirmation('¿Correr otro escenario?', 'no')
        ->expectsOutputToContain('Descuento aplicado (hipotético si la orden bloqueó): 1')
        ->expectsOutputToContain('max_discount_amount — descuento calculado (ya con cap aplicado si correspondía): 1 | máximo configurado: 1')
        ->assertExitCode(0);
});
