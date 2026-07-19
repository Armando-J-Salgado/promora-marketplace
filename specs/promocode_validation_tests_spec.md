# Promocode Validation Tests Specification

This document provides a specification for developing unitary and integrated tests for the `PromocodeValidationService` and its related handlers (`ExistenceValidator`, `ValidityValidator`, `StateValidator`), following a Test-Driven Development (TDD) approach using Pest.

## Testing Setup

- **Framework**: Pest PHP.
- **Run Command**: `php artisan test`
- **Environment**: Ensure the application uses the `.env.testing` configuration when running tests to avoid affecting the local development database. Since migrations have not been made for the testing database yet, Laravel's `RefreshDatabase` trait (used by default in feature tests) will automatically run the migrations against the testing database in memory or as configured in `.env.testing`.

## Directory Structure

Following the pattern of the `app` folder, the test files will be structured as follows:

```
tests/
├── Feature/
│   ├── Services/
│   │   └── PromocodeValidationServiceTest.php
│   └── Validations/
│       ├── ExistenceValidatorTest.php
│       ├── StateValidatorTest.php
│       └── ValidityValidatorTest.php
└── Unit/
    ├── Services/
    │   └── PromocodeValidationServiceTest.php
    └── Validations/
        ├── ExistenceValidatorTest.php
        ├── StateValidatorTest.php
        └── ValidityValidatorTest.php
```

## Unit Tests Specifications

Unit tests will focus purely on the logic within the class. For these handlers, unit tests ensure the isolated logic responds correctly to different input objects without relying on a real database connection.

### 1. `tests/Unit/Validations/ExistenceValidatorTest.php`

**Goal**: Confirm the promocode still exists at the moment of making the validation.
- **Scenario 1**: When a promocode exists (mocked `find()` returns a model), it should pass validation by calling `parent::handle()` and not throw an exception.
- **Scenario 2**: When a promocode does not exist (mocked `find()` returns `null`), it should throw an `InvalidArgumentException` with the message: *"El código promocional no existe"*.

### 2. `tests/Unit/Validations/ValidityValidatorTest.php`

**Goal**: Confirm that today we are in the valid exchange period of the promocode.
- **Scenario 1**: When the current date is strictly after `activation_date` and before `expiration_date` (valid period), it should pass validation.
- **Scenario 2**: When the current date is before `activation_date` (future activation), it should throw an `InvalidArgumentException` with the message: *"El código promocional aún no comienza su período de canje"*.
- **Scenario 3**: When the current date is after `expiration_date` (past expiration), it should throw an `InvalidArgumentException` with the message: *"El código promocional ha caducado"*.

### 3. `tests/Unit/Validations/StateValidatorTest.php`

**Goal**: Confirm the promocode is active at the moment.
- **Scenario 1**: When the promocode `status` attribute is exactly `'active'`, it should pass validation.
- **Scenario 2**: When the promocode `status` attribute is not `'active'` (e.g., `'inactive'` or `'used'`), it should throw an `InvalidArgumentException` with the message: *"El código no se encuentra activo"*.

### 4. `tests/Unit/Services/PromocodeValidationServiceTest.php`

**Goal**: Confirm the service correctly assembles and executes the validation chain based on the rules.
- **Scenario 1**: When calling `validate()`, it should initiate the chain starting with `ExistenceValidator`, instantiate subsequent handlers using `ValidationFactory` based on `$promocode->rules`, chain them sequentially via `setNext()`, and invoke `handle()`.
- **Scenario 2**: If the chain executes without throwing any exceptions, the service should return `true`.

---

## Integration / Feature Tests Specifications

Feature tests will interact with the database using the `.env.testing` configuration, ensuring that the components work seamlessly together as they would in production. These tests will leverage Model Factories to prepare the real database state.

### 1. `tests/Feature/Validations/ExistenceValidatorTest.php`

- **Scenario 1**: Create a real Promocode record in the database using a factory. Pass this instance to `ExistenceValidator->handle()`. Expect no exception to be thrown.
- **Scenario 2**: Instantiate a Promocode with an ID that has been deleted or does not exist in the database. Pass this instance to `ExistenceValidator->handle()`. Expect it to throw the *"El código promocional no existe"* `InvalidArgumentException`.

### 2. `tests/Feature/Validations/ValidityValidatorTest.php`

- **Scenario 1**: Create a Promocode in the database where `activation_date` is a past date and `expiration_date` is a future date. Expect the handler to pass without exceptions.
- **Scenario 2**: Create a Promocode where `activation_date` is a future date (e.g., `now()->addDays(2)`). Expect the handler to throw an `InvalidArgumentException`.
- **Scenario 3**: Create a Promocode where `expiration_date` is a past date (e.g., `now()->subDays(2)`). Expect the handler to throw an `InvalidArgumentException`.

### 3. `tests/Feature/Validations/StateValidatorTest.php`

- **Scenario 1**: Create a Promocode in the database with `status` set to `'active'`. Expect the handler to pass without exceptions.
- **Scenario 2**: Create a Promocode in the database with `status` set to `'inactive'`. Expect the handler to throw an `InvalidArgumentException`.

### 4. `tests/Feature/Services/PromocodeValidationServiceTest.php`

- **Scenario 1 (Happy Path)**: Create a valid Order and a valid, active Promocode in the database with appropriate rules mapped in `$promocode->rules`. Call `$service->validate($order, $promocode)`. Expect it to return `true`.
- **Scenario 2 (Chain Failure)**: Create a valid Order but an inactive or expired Promocode in the database. Call `$service->validate()`. Expect the exact `InvalidArgumentException` corresponding to the validator that failed to be bubbled up, proving that the chain is correctly assembled and fails fast on the first invalid condition.
