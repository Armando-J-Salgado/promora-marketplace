# Plan: Crear CLAUDE.md para promora-marketplace

## Context

El usuario ejecutó `/init` para generar un CLAUDE.md que oriente a futuras instancias de Claude Code. Aclaró explícitamente: el proyecto se desarrolla con **TDD — los tests son la fuente de verdad y NO deben modificarse**, y la carpeta `plans/` guarda planes de implementación y specs. No existe CLAUDE.md; sí existe AGENTS.md (guidelines de Laravel Boost) que no hay que duplicar, solo referenciar.

## Hallazgos clave de la exploración

- Laravel 13 / PHP 8.4, Pest v4, Pint, Laravel Boost, servido por Laravel Herd. README es el default de Laravel (nada que incluir).
- Tests: `phpunit.xml` usa SQLite `:memory:`; `Pest.php` aplica `RefreshDatabase` a **Feature y Unit**. Suite completa en `tests/Unit` y `tests/Feature` (validators, service, engine, discounts, logger, controller).
- Dominio: motor de códigos promocionales para un marketplace de servicios. Patrones de diseño (curso de Patrones):
  - **Chain of Responsibility**: `app/Validations/PromocodeValidationHandler.php` (abstracto) + 11 validators concretos; `PromocodeValidationService` arma la cadena dinámicamente desde `$promocode->rules` (JSON) usando `ValidationFactory::make($key)` — las claves de `rules` mapean 1:1 a validators. Siempre inicia con `ExistenceValidator`. Los validators lanzan `InvalidArgumentException` con mensajes en español (los tests asertan el mensaje exacto).
  - **Factory**: `ValidationFactory`, `DiscountFactory` (por `$promocode->type`: fixed/percent/tiered/default).
  - **Template Method**: `DiscountTemplate::calculatePrice()` con `applyDiscount()` abstracto.
  - **Singleton**: `app/Logger/Logger.php`.
  - **Facade/orquestador**: `PromocodeEngine` (validación → `PriceCalculatorService` → log).
- Flujo HTTP: `routes/api.php` → `POST v1/orders/{order}/promocode/{promocode}` → `OrderController::validate` → `PromocodeEngine`.
- Modelos: Customer, Order, Service, Category (jerarquía parent/child), Promocode (rules JSON), PromocodeRedemption, Tier; pivots `customer_promocode` (allowedCustomers) y `service_order` (con quantity). `Order::getOrderContext()` produce `App\Orderable\OrderContext`.
- `PriceCalculatorService` es un stub pendiente ("Completar lógica aquí").
- Controllers/Policies/Requests son scaffolds vacíos salvo `OrderController::validate`.
- `plans/*.md` contiene los planes/specs TDD del proyecto.

## Archivo a crear

`CLAUDE.md` en la raíz del repo, con el prefijo obligatorio y estas secciones:

1. **Prefijo estándar** exigido por /init.
2. **Metodología TDD (regla crítica)**: los tests en `tests/` son la fuente de verdad; **nunca modificar ni borrar tests** — si un test falla, se arregla el código de producción. Los planes/specs viven en `plans/*.md` y ahí se guardan nuevos planes de implementación.
3. **Comandos**:
   - `php artisan test --compact` (suite completa), `php artisan test --compact --filter=NombreTest` (un test), suites `--testsuite=Unit|Feature`.
   - `vendor/bin/pint --dirty --format agent` (formato, obligatorio tras editar PHP).
   - `composer dev` (server + queue + vite), `composer setup`; app servida por Herd (no levantar servidor manualmente).
   - Tests corren contra SQLite `:memory:`; `RefreshDatabase` aplica a Unit y Feature.
4. **Arquitectura** (big picture): dominio del motor de promocodes, el flujo HTTP→Engine→Service→Chain, y los patrones enumerados arriba con sus rutas; regla de que agregar un validator implica registrar su clave en `ValidationFactory` y que la clave coincide con la de `rules`; excepciones `InvalidArgumentException` con mensajes exactos en español asertados por tests; jerarquía de categorías relevante para `ElegibleCategoriesValidator`; `OrderContext` como DTO.
5. **Estado actual**: `PriceCalculatorService` stub; controllers/policies scaffolds sin lógica (solo `OrderController::validate` implementado).
6. **Nota sobre AGENTS.md**: referencia a que contiene las guidelines de Laravel Boost (make: commands, factories con estados nombrados, Pint, no crear docs sin pedirlo) — sin duplicar su contenido.

## Verificación

- Releer el CLAUDE.md generado y contrastar comandos contra `composer.json` / AGENTS.md.
- No se ejecuta código; es solo documentación.
