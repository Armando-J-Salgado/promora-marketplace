# `promocode:play` — herramienta de prueba en vivo

Comando Artisan para probar el motor de códigos promocionales sin escribir un test nuevo cada vez. Pensado para la defensa oral (demo en vivo) y para que cualquiera del equipo pueda ejercitar la cadena de validación manualmente.

## Requisitos

- Proyecto instalado (`composer install`, `.env` con `APP_KEY`, migraciones corridas).
- Correr los comandos con el PHP de Herd. Si `php artisan` no es reconocido en tu shell, usa el binario de Herd directamente o agrega su carpeta `bin` al PATH.

```bash
php artisan migrate   # si no tienes las tablas creadas todavía
```

> ⚠️ Este comando persiste datos reales en tu base de datos local (`database/database.sqlite` u otra que tengas configurada) — crea `Customer`, `Order`, `Promocode`, etc. de verdad, no usa una transacción de prueba. Si quieres partir de una BD limpia antes/después de una demo, corre `php artisan migrate:fresh`.

## Modo demo (recorrido automático)

Recorre los 11 validators de la cadena (en el orden del TDR), dos corridas por cada uno: un caso que **debe bloquear** el código y uno que **debe permitirlo**. Por cada corrida imprime el resultado y el log generado por esa validación específica.

```bash
php artisan promocode:play --demo --no-pause
```

- Sin `--no-pause`, el comando pausa (`Presiona ENTER para continuar…`) entre cada uno de los 22 casos — útil para ir explicando en vivo durante la defensa.
- Al final imprime una tabla resumen (`Validator | Caso | Resultado`) con `OK`/`MISMATCH` por caso, y termina con exit code `0` si los 22 casos coincidieron con lo esperado, `1` si no.

### Qué esperar de cada validator

| Validator | Código semántico (sección 7 TDR) |
|---|---|
| `ExistenceValidator` | `invalid_code` |
| `StateValidator` | `invalid_code` |
| `ElegibleCategoriesValidator` | `invalid_code` |
| `ValidityValidator` | `expired_coupon` |
| `MinPurchaseValidator` | `min_amount_required` |
| `FirstOrderValidator` | `code_already_used` |
| `UserUsageValidator` | `usage_limit_reached` |
| `GlobalUsageValidator` | `usage_limit_reached` |
| `RestrictedUsageValidator` | `restricted_usage` |
| `GlobalAmountValidator` | `maximum_discount_reached` |
| `MaxDiscountValidator` | `maximum_discount_reached` |

**Nota de diseño**: `GlobalAmountValidator` y `MaxDiscountValidator` no son parte de la cadena de `PromocodeValidationService` — son un paso del Template Method de `DiscountTemplate` (Fase 2 — Cálculo), que corre después de calcular el descuento. Por eso `promocode:play` valida a través de `PromocodeEngine::validateCode()` (Fase 1 + Fase 2, la misma ruta que usa el endpoint HTTP real), no solo del servicio de validación — así el descuento real sí se calcula y se usa.

Por diseño del motor, exceder `max_discount_amount` *cappea* el descuento en vez de bloquear la orden (solo `global_amount_limit` relanza la excepción). Por eso el caso "bloqueado" de `MaxDiscountValidator` en la demo solo puede representar la rama de *regla mal configurada* (`max_discount_amount` no definido), nunca la de "descuento que sobrepasa el límite" — es comportamiento de negocio real, no una limitación de la herramienta.

`PromocodeValidationService` internamente sigue incluyendo estas dos reglas en su propia cadena (Fase 1, con discount fijo en `0.0`) — es una redundancia conocida del motor, fuera del alcance de esta herramienta.

## Modo interactivo (armar tu propio escenario)

```bash
php artisan promocode:play
```

Te va guiando con prompts para:
1. Elegir el tipo de código (`fixed` / `percent` / `tiered`).
2. Elegir qué reglas activar (multiselect: monto mínimo, categorías elegibles, primera orden, límites de uso, uso restringido, descuento máximo, etc.).
3. Opcionalmente forzar un estado no estándar del código (pausado / caducado / aún no vigente).
4. **Armar la orden servicio por servicio**: cuántos servicios tendrá, y por cada uno su precio y cantidad exactos (el subtotal final es la suma real de lo que armaste, no un valor al azar). Para la categoría de cada servicio (a partir del segundo) eliges entre: categoría nueva sin relación, reusar una ya creada en este escenario, categoría nueva **hija** de una ya creada, o categoría nueva que pasa a ser **padre** de una ya existente (reparenta la categoría existente) — así podés demostrar en vivo la coincidencia por jerarquía padre/hijo de `ElegibleCategoriesValidator`, no solo la coincidencia exacta.
5. Completar los parámetros de cada regla elegida (montos, límites, cuántas órdenes/redenciones previas simular). Si activaste `elegible_categories`, eliges cuáles de las categorías que acabas de crear cuentan como elegibles (podés marcar varias, o ninguna para forzar el bloqueo).
6. Si el tipo es `tiered`: cuántos tramos (tiers) tendrá el código y el `minimum_orders`/`discount_value` de cada uno, más cuántas órdenes históricas *no canceladas/no borrador* simular para el cliente (es justo lo que usa `TieredDiscount` para elegir el tramo). Antes de este cambio, `tiered` en la CLI siempre daba 0% de descuento porque nunca se creaban `Tier` reales.

Al final corre la validación contra el `Order`/`Promocode` recién creados y muestra si quedó **VÁLIDO** o **BLOQUEADO** (con el mensaje de la excepción), más el log de esa corrida específica (no el acumulado de corridas anteriores en la misma sesión del comando), más el **desglose de precio** (ver abajo). Luego pregunta si quieres armar otro escenario.

## Desglose de precio

Después de cada corrida (tanto en `--demo` como en modo interactivo) se imprime un bloque `--- Desglose de precio ---` con:

- Subtotal calculado, tipo y valor configurado del código.
- Descuento aplicado y precio final — calculados de forma independiente al resultado de validación, así que si la orden bloqueó en Fase 1 (antes de llegar al cálculo real) esto muestra el valor **hipotético** (qué hubiera pasado de no bloquear), marcado explícitamente como tal. Es útil para explicar en la defensa qué habría pasado en cada rama.
- Si el tipo es `tiered`: cuántas órdenes históricas cuenta el motor para este cliente y qué tramo (si alguno) calzó.
- Por cada regla activa con un umbral numérico (`min_purchase_amount`, `user_usage_limit`, `global_usage_limit`, `global_amount_limit`, `max_discount_amount`): el valor real observado junto al umbral configurado — sin veredicto derivado (el PASS/FAIL real ya está en los logs impresos justo arriba). En `max_discount_amount`, si el descuento fue *cappeado*, el valor mostrado ya es el post-cap (coincide con el umbral, lo cual demuestra visualmente el cap).

## Formato de los logs

Cada validator deja rastro en `App\Logger\Logger` (singleton, accesible vía `Logger::getInstance()->getLogs()`) con este formato:

```
[FAIL] {NombreValidator} | code={codigo_semantico} | promocode=#{id} | order=#{id} | {mensaje}
[PASS] {NombreValidator} | promocode=#{id} | order=#{id} | regla superada
```

Esto es lo que ves impreso en cada corrida del comando, y es lo que assertan los tests en `tests/Unit/Validations/`.

## Para el equipo: dónde está cada pieza

- `app/Console/Commands/PromocodePlayCommand.php` — el comando en sí (modo `--demo` y modo interactivo).
- `app/Support/Promocode/PromocodeRuleInspector.php` — helper de solo lectura que expone los valores "reales" (usos previos, monto redimido, órdenes históricas, tramo calzado) que usa el desglose de precio, ejecutando las mismas queries que ya corren los validators/`TieredDiscount` — no reimplementa ningún operador de comparación.
- `app/Support/Promocode/PromocodeScenarioFactory.php` — construye los 22 casos (bloqueado/permitido × 11 validators) usados por `--demo`. Reutiliza los named states de `database/factories/PromocodeFactory.php`.
- `tests/Feature/Console/PromocodePlayCommandTest.php` y `tests/Feature/Support/PromocodeScenarioFactoryTest.php` — cobertura automatizada del modo demo y de los escenarios.
- `tests/Feature/Console/PromocodePlayInteractiveCommandTest.php` — cobertura del modo interactivo: multi-servicio con subtotal exacto, jerarquía de categorías (`__child`/`__parent`), `elegible_categories` con selección real, `tiered` con tiers reales, y el cap de `max_discount_amount`.
- Más contexto de diseño y las decisiones de alcance tomadas: `plans/live_testing_and_validation_logging_plan.md`.
