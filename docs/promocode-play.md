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
| `MaxDiscountValidator` | `maximum_discount_reached` (ver limitación conocida abajo) |

**Limitación conocida**: `PromocodeValidationService::validate()` nunca calcula el descuento real antes de armar la cadena (siempre pasa `0.0` a `ValidationFactory::make()`). Por eso el caso "bloqueado" de `MaxDiscountValidator` en la demo solo puede mostrar la rama de *regla mal configurada* (`max_discount_amount` no definido), no la rama de *descuento que sobrepasa el límite* — esa rama es inalcanzable por la cadena real tal como está hoy. Es un bug preexistente, documentado, y está fuera de alcance de esta herramienta.

## Modo interactivo (armar tu propio escenario)

```bash
php artisan promocode:play
```

Te va guiando con prompts para:
1. Elegir el tipo de código (`fixed` / `percent` / `tiered`).
2. Elegir qué reglas activar (multiselect: monto mínimo, categorías elegibles, primera orden, límites de uso, uso restringido, descuento máximo, etc.).
3. Completar los parámetros de cada regla elegida (montos, límites, cuántas órdenes/redenciones previas simular).
4. Opcionalmente forzar un estado no estándar del código (pausado / caducado / aún no vigente).

Al final corre la validación contra el `Order`/`Promocode` recién creados y muestra si quedó **VÁLIDO** o **BLOQUEADO** (con el mensaje de la excepción), más el log de esa corrida específica (no el acumulado de corridas anteriores en la misma sesión del comando). Luego pregunta si quieres armar otro escenario.

## Formato de los logs

Cada validator deja rastro en `App\Logger\Logger` (singleton, accesible vía `Logger::getInstance()->getLogs()`) con este formato:

```
[FAIL] {NombreValidator} | code={codigo_semantico} | promocode=#{id} | order=#{id} | {mensaje}
[PASS] {NombreValidator} | promocode=#{id} | order=#{id} | regla superada
```

Esto es lo que ves impreso en cada corrida del comando, y es lo que assertan los tests en `tests/Unit/Validations/`.

## Para el equipo: dónde está cada pieza

- `app/Console/Commands/PromocodePlayCommand.php` — el comando en sí (modo `--demo` y modo interactivo).
- `app/Support/Promocode/PromocodeScenarioFactory.php` — construye los 22 casos (bloqueado/permitido × 11 validators) usados por `--demo`. Reutiliza los named states de `database/factories/PromocodeFactory.php`.
- `tests/Feature/Console/PromocodePlayCommandTest.php` y `tests/Feature/Support/PromocodeScenarioFactoryTest.php` — cobertura automatizada del modo demo y de los escenarios.
- Más contexto de diseño y las decisiones de alcance tomadas: `plans/live_testing_and_validation_logging_plan.md`.
