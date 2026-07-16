# Implementation Plan: Promocode Validation TDD Tests

## Overview

This plan implements a full TDD test suite for the `PromocodeValidationService` and its three active handlers: `ExistenceValidator`, `ValidityValidator`, and `StateValidator`. The suite includes both **Unit** tests (isolated logic, no DB) and **Feature** tests (real database interactions via `.env.testing`).

The Chain of Responsibility pattern used in the production code drives the test strategy: each handler must be testable in isolation (unit) and as part of a real data flow (feature).

> [!IMPORTANT]
> `RefreshDatabase` is currently commented out in `Pest.php`. It must be enabled for Feature tests to run against the `.env.testing` database. This will be done as the **first step** of the implementation.

> [!NOTE]
> Both `PromocodeFactory` and `OrderFactory` (and `CustomerFactory`, which `OrderFactory` depends on) are empty. Filling them is a prerequisite for all Feature tests and is included as a dedicated step.

---

## Proposed Changes

### Step 1 — Enable `RefreshDatabase` for Feature Tests

#### [MODIFY] [Pest.php](file:///c:/Users/arjsa/Herd/promora-marketplace/tests/Pest.php)

Uncomment `->use(RefreshDatabase::class)` so that all Feature tests automatically run migrations against the `.env.testing` database and roll back after each test.

```diff
- pest()->extend(TestCase::class)
-  // ->use(RefreshDatabase::class)
-     ->in('Feature');
+ pest()->extend(TestCase::class)
+     ->use(RefreshDatabase::class)
+     ->in('Feature');
```

---

### Step 2 — Fill Factories

All three factories must return a complete default state. Test states that require deviation from the default (e.g., inactive promocode, future activation date) will be expressed via **named factory states** defined in the same factory class.

---

#### [MODIFY] [CustomerFactory.php](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/CustomerFactory.php)

`Customer` is a required foreign key for `Order`. The factory must return valid unique `name`, `email`, and `DUI` fields.

```php
public function definition(): array
{
    return [
        'name'  => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'DUI'   => fake()->unique()->numerify('#########'),
    ];
}
```

---

#### [MODIFY] [OrderFactory.php](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/OrderFactory.php)

`Order` requires `status`, `subtotal`, `total`, and a `customer_id` foreign key. The factory default will create its own `Customer`.

```php
public function definition(): array
{
    return [
        'status'      => 'pending',
        'subtotal'    => fake()->randomFloat(2, 50, 500),
        'total'       => fake()->randomFloat(2, 50, 500),
        'customer_id' => Customer::factory(),
    ];
}
```

---

#### [MODIFY] [PromocodeFactory.php](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/PromocodeFactory.php)

The **default state** represents a fully valid, active promocode (existence confirmed, valid date range, active status). Deviating states are expressed as named states.

```php
public function definition(): array
{
    return [
        'type'            => 'percentage',
        'rules'           => ['validity' => true, 'state' => true],
        'status'          => 'active',
        'value'           => fake()->randomFloat(2, 5, 50),
        'activation_date' => now()->subDay(),
        'expiration_date' => now()->addDays(30),
    ];
}

// Named state: promocode activation period has not started yet
public function notYetActive(): static
{
    return $this->state([
        'activation_date' => now()->addDays(5),
        'expiration_date' => now()->addDays(35),
    ]);
}

// Named state: promocode redemption period has already passed
public function expired(): static
{
    return $this->state([
        'activation_date' => now()->subDays(30),
        'expiration_date' => now()->subDay(),
    ]);
}

// Named state: promocode is not active
public function inactive(): static
{
    return $this->state([
        'status' => 'inactive',
    ]);
}
```

---

### Step 3 — Unit Tests

Unit tests instantiate validators directly and pass plain Eloquent model instances (not persisted to DB). `Promocode::find()` in `ExistenceValidator` is mocked using Mockery / Laravel's built-in mocking helpers.

---

#### [NEW] `tests/Unit/Validations/ExistenceValidatorTest.php`

**Setup**: An `Order` and a `Promocode` are instantiated with `new` (not factory) and given a fake `id`.

| # | Test description | Expected result |
|---|---|---|
| 1 | `handle()` called with a promocode whose `id` exists in the DB (mock `Promocode::find()` returns model) | No exception thrown |
| 2 | `handle()` called with a promocode whose `id` does NOT exist (mock `Promocode::find()` returns `null`) | Throws `InvalidArgumentException` with message `'El código promocional no existe'` |

```php
// Example skeleton
it('passes when the promocode exists', function () {
    Promocode::shouldReceive('find')->with(1)->andReturn(new Promocode());
    $validator = new ExistenceValidator();
    expect(fn() => $validator->handle(new Order(), tap(new Promocode(), fn($p) => $p->id = 1)))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the promocode does not exist', function () {
    Promocode::shouldReceive('find')->with(99)->andReturn(null);
    $validator = new ExistenceValidator();
    expect(fn() => $validator->handle(new Order(), tap(new Promocode(), fn($p) => $p->id = 99)))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no existe');
});
```

---

#### [NEW] `tests/Unit/Validations/ValidityValidatorTest.php`

**Setup**: `Promocode` instances are created with `new` and their `activation_date` / `expiration_date` are set as Carbon instances directly on the model.

| # | Test description | Expected result |
|---|---|---|
| 1 | `activation_date` is in the past, `expiration_date` is in the future | No exception thrown |
| 2 | `activation_date` is in the future | Throws `InvalidArgumentException` with message `'El código promocional aún no comienza su período de canje'` |
| 3 | `expiration_date` is in the past | Throws `InvalidArgumentException` with message `'El código promocional ha caducado'` |

```php
// Example skeleton
it('passes when within the valid period', function () {
    $promocode = new Promocode();
    $promocode->activation_date = now()->subDay();
    $promocode->expiration_date = now()->addDay();

    $validator = new ValidityValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});
```

---

#### [NEW] `tests/Unit/Validations/StateValidatorTest.php`

**Setup**: `Promocode` instances are created with `new` and `status` is set directly.

| # | Test description | Expected result |
|---|---|---|
| 1 | `status === 'active'` | No exception thrown |
| 2 | `status === 'inactive'` | Throws `InvalidArgumentException` with message `'El código no se encuentra activo'` |
| 3 | `status === 'used'` (any non-active value) | Throws `InvalidArgumentException` with message `'El código no se encuentra activo'` |

---

#### [NEW] `tests/Unit/Services/PromocodeValidationServiceTest.php`

**Setup**: Validators and factory are mocked. Validates the service's chain-assembly logic.

| # | Test description | Expected result |
|---|---|---|
| 1 | `validate()` returns `true` when the full chain passes without exceptions | Returns `true` |
| 2 | `validate()` propagates an `InvalidArgumentException` thrown by any handler in the chain | Exception bubbles up unchanged |

---

### Step 4 — Feature Tests

Feature tests use the actual database (`.env.testing`) with `RefreshDatabase`. All models are created using factories. The goal is to verify real end-to-end DB interactions.

---

#### [NEW] `tests/Feature/Validations/ExistenceValidatorTest.php`

| # | Test description | Expected result |
|---|---|---|
| 1 | Create a `Promocode` via factory (persisted to DB). Call `handle()` with that instance. | No exception thrown |
| 2 | Create a `Promocode` via factory, delete it from DB, then call `handle()` with the stale instance. | Throws `InvalidArgumentException` with message `'El código promocional no existe'` |

---

#### [NEW] `tests/Feature/Validations/ValidityValidatorTest.php`

| # | Test description | Expected result |
|---|---|---|
| 1 | Create a `Promocode` with default factory state (valid date range). Call `handle()`. | No exception thrown |
| 2 | Create a `Promocode` with `Promocode::factory()->notYetActive()` state. Call `handle()`. | Throws `InvalidArgumentException` (`'aún no comienza'`) |
| 3 | Create a `Promocode` with `Promocode::factory()->expired()` state. Call `handle()`. | Throws `InvalidArgumentException` (`'ha caducado'`) |

---

#### [NEW] `tests/Feature/Validations/StateValidatorTest.php`

| # | Test description | Expected result |
|---|---|---|
| 1 | Create a `Promocode` with default factory state (`status = 'active'`). Call `handle()`. | No exception thrown |
| 2 | Create a `Promocode` with `Promocode::factory()->inactive()` state. Call `handle()`. | Throws `InvalidArgumentException` (`'El código no se encuentra activo'`) |

---

#### [NEW] `tests/Feature/Services/PromocodeValidationServiceTest.php`

| # | Test description | Setup | Expected result |
|---|---|---|---|
| 1 | Happy path: valid order + fully valid promocode | Default factory for both. `rules` includes `validity` and `state`. | Returns `true` |
| 2 | Chain failure — inactive promocode | `Promocode::factory()->inactive()`. `rules` includes `state`. | Throws `InvalidArgumentException` (`'El código no se encuentra activo'`) |
| 3 | Chain failure — expired promocode | `Promocode::factory()->expired()`. `rules` includes `validity`. | Throws `InvalidArgumentException` (`'ha caducado'`) |

---

## File Summary

| File | Action |
|---|---|
| [Pest.php](file:///c:/Users/arjsa/Herd/promora-marketplace/tests/Pest.php) | MODIFY — uncomment `RefreshDatabase` |
| [CustomerFactory.php](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/CustomerFactory.php) | MODIFY — fill `definition()` |
| [OrderFactory.php](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/OrderFactory.php) | MODIFY — fill `definition()` with Customer dependency |
| [PromocodeFactory.php](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/PromocodeFactory.php) | MODIFY — fill `definition()` + add named states |
| `tests/Unit/Validations/ExistenceValidatorTest.php` | NEW |
| `tests/Unit/Validations/ValidityValidatorTest.php` | NEW |
| `tests/Unit/Validations/StateValidatorTest.php` | NEW |
| `tests/Unit/Services/PromocodeValidationServiceTest.php` | NEW |
| `tests/Feature/Validations/ExistenceValidatorTest.php` | NEW |
| `tests/Feature/Validations/ValidityValidatorTest.php` | NEW |
| `tests/Feature/Validations/StateValidatorTest.php` | NEW |
| `tests/Feature/Services/PromocodeValidationServiceTest.php` | NEW |

---

## Verification Plan

### Automated Tests

```bash
php artisan test
```

All tests should pass. No test should leave state in the database (guaranteed by `RefreshDatabase`).

### Specific Filters (for iterative development)

```bash
# Run only unit validator tests
php artisan test --compact --filter=ExistenceValidatorTest

# Run only feature service tests
php artisan test --compact --filter=PromocodeValidationServiceTest
```
