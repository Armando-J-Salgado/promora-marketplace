# Plan: Logging semántico en validaciones + herramienta de prueba en vivo

## Contexto

Este proyecto es la implementación funcional del examen final de Patrones de Diseño (TDR-PROMO-001, `plans/indicaciones.md`): un motor de códigos promocionales (Chain of Responsibility + Factory + Template Method + Singleton). Hay una **defensa oral el viernes 24 de julio de 2026** donde el profesor puede pedir ver el motor funcionando en vivo y puede preguntar sobre el manejo de errores semántico exigido por la sección 7 del TDR.

Hoy la cadena de validación funciona pero es una caja negra: ningún validator individual deja rastro de qué evaluó ni por qué falló/pasó (solo `PromocodeEngine` loguea un mensaje genérico de éxito/fallo final), y no hay ninguna forma de ejercitar el motor manualmente sin escribir un test Pest nuevo cada vez. Esta rama construye dos piezas de apoyo para la defensa:

1. **Logging granular por validador**, con el código de error semántico de la sección 7 del TDR (gap detectado: hoy no existe en ningún lado del código).
2. **Un comando Artisan (`promocode:play`)** — el primero del proyecto — para armar escenarios en vivo y correr un recorrido automático (`--demo`) que muestra las 11 reglas fallando y pasando, pensado para proyectarse en la defensa oral.

### Decisiones de alcance ya tomadas (no reabrir sin razón)

- La cadena de validación sigue siendo **fail-fast** tal cual existe hoy; no se restructura a "reporte completo de todas las reglas".
- Los escenarios del comando se **persisten de verdad** en la BD (factories `->create()`, sin transacción/rollback) — varios validators (`ExistenceValidator`, `UserUsageValidator`, `GlobalUsageValidator`, `RestrictedUsageValidator`, `FirstOrderValidator`) hacen queries reales a la BD y necesitan IDs reales.
- El logging se implementa **explícito dentro de cada uno de los 11 validators** (sin refactor a Template Method en `PromocodeValidationHandler`), y el código semántico **solo vive en el string del log**, no en una excepción nueva — los mensajes/clases de excepción existentes no se tocan (los tests los assertan literal).
- **Fuera de alcance explícito**: el bug de `PromocodeValidationService::validate()` que nunca pasa el `$discount` real a `ValidationFactory::make()` (siempre `0.0`) — `GlobalAmountValidator` se ve afectado solo parcialmente (su chequeo principal usa el histórico de BD, no `$discount`), pero `MaxDiscountValidator` sí depende 100% de `$discount`, por lo que su rama "sobrepasa el límite" es **inalcanzable** por la cadena real tal como está. Se documenta como limitación conocida, no se arregla aquí.

## 1. Mapeo validator → código de error semántico (sección 7 del TDR)

| Validator | Código | Nota |
|---|---|---|
| `ExistenceValidator` | `invalid_code` | "Código inexistente" está literal en la definición del TDR |
| `StateValidator` | `invalid_code` | "...inactivo..." está literal en la misma definición |
| `ElegibleCategoriesValidator` | `invalid_code` | "...categoría no elegible" está literal en la misma definición |
| `ValidityValidator` | `expired_coupon` | cubre ambas ramas (aún no vigente / caducado); el TDR no distingue las dos, se reutiliza el mismo código para ambas |
| `MinPurchaseValidator` | `min_amount_required` | rama "subtotal insuficiente" es match directo; rama "mínimo no configurado" es un error de config, reutiliza el mismo código por no haber uno mejor |
| `FirstOrderValidator` | `code_already_used` | match literal ("primera compra en usuario con historial") |
| `UserUsageValidator` | `usage_limit_reached` | match literal ("...por usuario") |
| `GlobalUsageValidator` | `usage_limit_reached` | rama "superado" es match directo; rama "límite no definido" reutiliza el código por ser error de config |
| `RestrictedUsageValidator` | `restricted_usage` | match exacto |
| `GlobalAmountValidator` | `maximum_discount_reached` | rama "supera presupuesto" es match directo; rama "límite no configurado" reutiliza el código |
| `MaxDiscountValidator` | `maximum_discount_reached` | **mapeo forzado**: el TDR define este código específicamente para el límite *acumulado global* (`global_amount_limit`), no para el tope *por transacción* (`max_discount_amount`, regla de post-cálculo, sección 1.3). No hay código dedicado en la tabla del TDR para esta regla — se documenta como gap a mencionar en el ASD (sección de trade-offs), no como decisión satisfactoria |

## 2. Formato de log (consistente en los 11 archivos)

```
FAIL: "[FAIL] {ValidatorName} | code={semantic_code} | promocode=#{$promocode->id} | order=#{$order->id} | {mensaje exacto de la excepción existente}"
PASS: "[PASS] {ValidatorName} | promocode=#{$promocode->id} | order=#{$order->id} | regla superada"
```

Una línea de `[FAIL]` antes de cada `throw` existente, una línea de `[PASS]` antes de cada `parent::handle(...)` existente — sin tocar el texto de los mensajes de excepción ni la lógica de control. Varios validators tienen más de un `throw`/`parent::handle()` (branches), así que el número de líneas de log varía (2–3), no es un límite físico de "2 líneas por archivo":

| Validator | Líneas de log |
|---|---|
| Existence, State, ElegibleCategories, RestrictedUsage | 1 fail + 1 pass |
| Validity, MinPurchase, GlobalUsage, GlobalAmount, MaxDiscount | 2 fail + 1 pass |
| FirstOrder, UserUsage | 1 fail + 2 pass (tienen un pass "temprano" — orden vacía / regla no definida — y un pass normal) |

No hay colisión con el formato de log ya usado por `PromocodeEngine` (`"Promocode #X aplicado..."` / `"Promocode inválido: #X..."`), porque estos siempre inician con `[FAIL]`/`[PASS]`.

## 3. `Logger`: añadir `getLogs()`

`app/Logger/Logger.php` — agregar:
```php
public function getLogs(): array
{
    return $this->logs;
}
```
Esto activa el test ya escrito pero inactivo `tests/Unit/PromocodeEngine/PromocodeEngine.php` (sin sufijo `Test.php`, PHPUnit/Pest no lo corre hoy). **Renombrarlo a `tests/Unit/PromocodeEngine/PromocodeEngineTest.php`** sin tocar su contenido — es la especificación que ya exige `getLogs()` retornando el string `"Promocode #{id} aplicado a orden #{id}. Precio final: 0"`.

No afecta a `tests/Unit/LoggerTest.php` ni `tests/Feature/Logger/LoggerIntegrationTest.php`: ambos acceden al array privado `logs` vía Reflection, no vía `getLogs()`.

## 4. Comando Artisan `promocode:play`

**Archivo**: `app/Console/Commands/PromocodePlayCommand.php` (Laravel 13 auto-descubre `app/Console/Commands/`, confirmado en `bootstrap/app.php` — no requiere registro manual).

**Firma**: `promocode:play {--demo} {--no-pause}`

**Librería de prompts**: `laravel/prompts` está confirmado como dependencia **directa** de `laravel/framework` en `composer.lock` (`"laravel/prompts": "^0.3.0"`, resuelta a v0.3.21) — siempre disponible. Usar `Laravel\Prompts\{select, text, confirm, multiselect, info, error, note, table, pause}` en vez de los helpers nativos de `Command`.

### Modo interactivo (default)

Loop de prompts: tipo de promocode → reglas configurables a activar (`multiselect` de las 8 claves de `rules`) → parámetros de cada regla elegida → estado especial opcional (paused/expired/notYetActive) → datos relacionados según reglas elegidas (categoría+servicio para `elegible_categories`, órdenes previas para `first_order_only`/`user_usage_limit`, redenciones previas para `global_usage_limit`/`global_amount_limit`, attach a `allowedCustomers()` para `restricted_usage`) → crea `Order`+`Promocode` con factories `->create()` → corre `PromocodeValidationService::validate()` capturando `InvalidArgumentException` → imprime resultado + el slice de `Logger::getInstance()->getLogs()` generado por esa corrida (usar `array_slice($logs, $startIdx)`, **no** el acumulado completo, porque el Logger es singleton y el comando puede correr muchas veces en el mismo proceso) → pregunta si repetir.

### Modo `--demo`

Recorre los 11 validators en el orden del TDR, dos corridas cada uno (bloqueado + permitido), usando `PromocodeScenarioFactory` (ver abajo) para construir cada par `[Order, Promocode]`. Por cada corrida: ejecuta la validación, muestra el resultado esperado vs. real, muestra el slice de logs de esa corrida, pausa (`pause()`, salvo `--no-pause`) antes de continuar. Al final, `table()` con resumen de los 22 casos y su match esperado/real. Exit code `0` si todos coinciden, `1` si no.

**Archivo nuevo**: `app/Support/Promocode/PromocodeScenarioFactory.php` — un método `blocked()`/`allowed()` por validator (11×2 = 22 métodos o 11 métodos que retornan un par), separado del comando para que sea testeable por Feature test de forma aislada y rápida (`RefreshDatabase`), sin I/O de consola.

Construcción de cada escenario (reutilizando los named states ya existentes en `PromocodeFactory`: `notYetActive()`, `expired()`, `paused()`, `withMinPurchase()`, `withGlobalUsageLimit()`, `withUserUsageLimit()`, `withGlobalAmountLimit()`, `withMaxDiscount()`, `withEligibleCategories()`, `firstOrderOnly()`):

| Validator | Bloqueado | Permitido |
|---|---|---|
| Existence | `Promocode` no persistido con `id` inexistente (`Promocode::max('id') + 9999`, `rules=[]`) — **no** crear-y-borrar, para no destruir datos reales en una demo en vivo | `Promocode::factory()->create()` normal |
| Validity | `->expired()->create()` | `->create()` default (rango ya válido) |
| State | `->paused()->create()` | `->create()` default (`status=active`) |
| ElegibleCategories | `withEligibleCategories([A.id])` + `Service` en categoría distinta | mismo promocode + `Service` en categoría `A` (o hija vía `withParent()`) |
| MinPurchase | `withMinPurchase(500)` + `Service` barato (subtotal < 500) | `withMinPurchase(50)` + subtotal ≥ 50 |
| FirstOrder | `firstOrderOnly()` + cliente con 1 orden previa + orden actual (segunda) | `firstOrderOnly()` + cliente sin órdenes previas |
| UserUsage | `withUserUsageLimit(1)` + 1 `PromocodeRedemption` previa del mismo cliente | `withUserUsageLimit(1)` + 0 redenciones previas |
| GlobalUsage | `withGlobalUsageLimit(1)` + 1 redención existente | `withGlobalUsageLimit(2)` + 1 redención (bajo el límite) |
| RestrictedUsage | regla activa, sin `allowedCustomers()->attach()` | mismo promocode + `attach($customer->id)` |
| GlobalAmount | `withGlobalAmountLimit(50)` + redención previa `discount_amount=100` | `withGlobalAmountLimit(50)` + redención previa `discount_amount=10` |
| MaxDiscount | **solo demostrable vía la rama de config faltante** (`rules['max_discount_amount']=null` explícito, construido con `->state(['rules'=>[...]])` en vez de `withMaxDiscount()`) — la rama "sobrepasa el límite" es inalcanzable por el bug de `$discount=0` fuera de alcance | `withMaxDiscount(20)` (pasa siempre por el mismo bug, documentado como limitación) |

## 5. Factories a completar

- **`database/factories/TierFactory.php`** — está vacía (`return [];`). Completar con `minimum_orders`, `discount_value`, `promocode_id` (columnas reales de la migración). Solo cosmético para que el modo interactivo pueda ofrecer `type=tiered` sin romper; la fase de validación en alcance no usa `Tier`.
- **`database/factories/PromocodeFactory.php`** — añadir el único helper de regla que falta: `restrictedUsage(): static` (mergea `rules['restricted_usage'] = true`), necesario para el escenario de `RestrictedUsageValidator` en `--demo`.

## 6. Archivos a crear/modificar (resumen)

**Producción:**
- `app/Logger/Logger.php` — `+getLogs()`
- `app/Validations/{Existence,State,Validity,ElegibleCategories,MinPurchase,FirstOrder,UserUsage,GlobalUsage,RestrictedUsage,GlobalAmount,MaxDiscount}Validator.php` — logging explícito, sin tocar mensajes/lógica
- `app/Console/Commands/PromocodePlayCommand.php` (nuevo)
- `app/Support/Promocode/PromocodeScenarioFactory.php` (nuevo)
- `database/factories/PromocodeFactory.php` — `+restrictedUsage()`
- `database/factories/TierFactory.php` — completar `definition()`

**Tests (TDD — escribir antes que la producción correspondiente):**
- Renombrar `tests/Unit/PromocodeEngine/PromocodeEngine.php` → `PromocodeEngineTest.php`
- Añadir assertions de logging (FAIL/PASS + código semántico) a los 11 `tests/Unit/Validations/*ValidatorTest.php` existentes, con `beforeEach` que resetea el singleton `Logger` por Reflection (mismo patrón que `tests/Unit/LoggerTest.php`)
- `tests/Feature/Support/PromocodeScenarioFactoryTest.php` (nuevo) — valida que cada `blocked()`/`allowed()` de los 11 validators produce el resultado esperado contra `PromocodeValidationService::validate()`
- `tests/Feature/Console/PromocodePlayCommandTest.php` (nuevo) — `$this->artisan('promocode:play', ['--demo'=>true,'--no-pause'=>true])->assertExitCode(0)` + `expectsOutputToContain()` para validar que aparecen los 11 nombres de validator y sus códigos semánticos

## 7. Orden de implementación recomendado

1. Renombrar el test huérfano → confirmar que falla solo por falta de `getLogs()` (rojo esperado, nada más roto).
2. Implementar `Logger::getLogs()` → confirmar que ese test pasa y `LoggerTest`/`LoggerIntegrationTest` siguen en verde.
3. Por cada uno de los 11 validators (uno a la vez, corriendo la suite completa entre cada uno): escribir primero las assertions de log en su test (rojo), luego añadir las líneas de log al validator (verde). Verificar diff línea por línea — el riesgo real es tocar sin querer una línea de `throw` existente al insertar el log justo antes.
4. Completar `TierFactory` y añadir `restrictedUsage()` a `PromocodeFactory`.
5. Construir `PromocodeScenarioFactory` con su test dedicado antes que el comando (lógica de escenarios validada de forma aislada, sin I/O de consola).
6. Construir `PromocodePlayCommand`, empezando por `--demo` (100% determinista, testeable con `$this->artisan()`); dejar el modo interactivo al final (su cobertura de test es best-effort — en la defensa se usa la app real, no el test).
7. Correr la suite completa (`php artisan test --compact`) y `vendor/bin/pint --dirty --format agent` antes de dar por terminado.

## 8. Verificación end-to-end

- `php artisan test --compact` — cero regresiones en los 11 validators, `Logger`, `PromocodeEngine`, y los nuevos tests pasando.
- `php artisan promocode:play --demo --no-pause` corrido manualmente en Herd — confirmar que las 22 corridas (11 reglas × bloqueado/permitido) muestran el resultado y log esperado, y que el caso `MaxDiscountValidator` "bloqueado" efectivamente no puede demostrar la rama "sobrepasa el límite" (limitación documentada, no bug de esta implementación).
- `php artisan promocode:play` (modo interactivo) corrido manualmente — armar al menos un escenario con reglas combinadas y confirmar que el resumen de logs mostrado corresponde solo a esa corrida (no acumulado de corridas previas).
