# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Metodología: TDD (regla crítica)

Este proyecto se desarrolla con **Test-Driven Development**. Los tests en `tests/Unit` y `tests/Feature` son la **fuente de verdad**.

- **Nunca modifiques ni borres un test para que pase.** Si un test falla, el bug está en el código de producción: arréglalo ahí.
- Los planes de implementación y specs viven en `plans/*.md`. Revísalos antes de implementar un cambio grande y guarda ahí los planes nuevos.

## Comandos

- `php artisan test --compact` — suite completa.
- `php artisan test --compact --filter=NombreDelTest` — un test o grupo específico.
- `php artisan test --compact --testsuite=Unit` / `--testsuite=Feature` — una suite.
- `vendor/bin/pint --dirty --format agent` — formatear; **obligatorio** después de editar cualquier archivo PHP.
- `composer dev` — levanta server + queue listener + vite concurrentemente (normalmente no hace falta: la app corre bajo **Laravel Herd**, no levantes un servidor manualmente).
- `composer setup` — instalación inicial (composer install, .env, key:generate, migrate, npm install/build).
- Los tests corren contra SQLite en memoria (`phpunit.xml`); `Pest.php` aplica `RefreshDatabase` tanto a `Unit` como a `Feature`.

## Arquitectura

Dominio: motor de códigos promocionales para un marketplace de servicios (customers, orders, services organizados en categorías jerárquicas).

Flujo HTTP: `routes/api.php` → `POST v1/orders/{order}/promocode/{promocode}` → `OrderController::validate` → `PromocodeEngine`.

`PromocodeEngine` orquesta: cadena de validación → `PriceCalculatorService` → `Logger`.

### Patrones de diseño en juego

- **Chain of Responsibility** — `app/Validations/PromocodeValidationHandler.php` (handler abstracto) + 11 validators concretos. `PromocodeValidationService` arma la cadena dinámicamente a partir de `$promocode->rules` (JSON), donde cada clave de `rules` mapea 1:1 a un validator vía `ValidationFactory::make($key)`. La cadena siempre empieza con `ExistenceValidator`. **Si agregas un validator nuevo, debes registrar su clave en `ValidationFactory`** y esa clave debe coincidir exactamente con la usada en `rules`. Los validators lanzan `InvalidArgumentException` con mensajes en español — los tests assertan el mensaje exacto, así que no cambies el texto sin actualizar el test que lo cubre (o mejor, sin que el test te obligue a ello).
- **Factory** — `ValidationFactory` (validators) y `DiscountFactory` (según `$promocode->type`: `fixed` / `percent` / `tiered` / `default`).
- **Template Method** — `DiscountTemplate::calculatePrice()`, con `applyDiscount()` abstracto implementado por cada tipo de descuento.
- **Singleton** — `app/Logger/Logger.php`.

### Modelos y datos

`Customer`, `Order`, `Service`, `Category` (jerarquía parent/child, relevante para `ElegibleCategoriesValidator`), `Promocode` (con `rules` en JSON), `PromocodeRedemption`, `Tier`. Pivots: `customer_promocode` (clientes permitidos) y `service_order` (con `quantity`). `Order::getOrderContext()` construye el DTO `App\Orderable\OrderContext`, que es el objeto que viaja a través del engine y los validators.

### Estado actual (WIP)

- `PriceCalculatorService` ya está implementado y testeado (`subtotal - descuento`, con piso en 0).
- Controllers, Policies y Form Requests son scaffolds vacíos, salvo `OrderController::validate` que ya está implementado.

## AGENTS.md

Este repo ya tiene `AGENTS.md` con las guidelines de Laravel Boost (uso de `php artisan make:*`, factories con estados nombrados en tests, `vendor/bin/pint`, no crear documentación sin que se pida explícitamente, etc.). Consúltalo directamente — no se duplica aquí.
