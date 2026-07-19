# Promocode Engine

This project implements a robust, extensible, and testable Promocode Engine within a Laravel application. The architecture is designed using several design patterns (Facade, Singleton, Chain of Responsibility, Factory, Template Method) to separate concerns and ensure maintainability.

## Architecture and Structure

The engine is composed of the following key components:

- **PromocodeEngine (Facade/Singleton):** Acts as the main entry point for the system. It separates the logic of code validation and application from the controllers by coordinating the necessary underlying services.

- **PromocodeValidationService:** Manages the logic for pre-discount calculation validations. It utilizes a `PromocodeHandler` abstract class implementing the Chain of Responsibility (CoR) pattern and a `ValidationFactory` to dynamically build the validation chain.

- **Validation Classes:** These classes extend the `PromocodeHandler` to incorporate specific validation rules (e.g., global usage limits, user usage limits, expiration dates). If a validation rule fails, the class throws a corresponding error/exception.

- **PriceCalculatorService:** Handles the core logic of discount calculation and validates the final amount to be discounted. It leverages a `DiscountTemplate` abstract class and a `DiscountFactory` to resolve the correct discount strategy, while also incorporating the aforementioned validation classes.

- **Discount Classes:** These classes extend the `DiscountTemplate` abstract class and implement the `applyDiscount` method to calculate the specific value of the discount (e.g., fixed amount, percentage).

- **Logger:** Implemented as a Singleton, this utility is crucial for tracking functionality, monitoring operations, and debugging the engine's internal processes.

- **OrderableInterface and OrderContext:** These structures make the Promocode Engine highly decoupled and flexible. By programming against the `OrderableInterface`, the engine can be used interchangeably across different entities that represent an order. The `OrderContext` class is responsible for gathering and passing all necessary information for the discount application process.

## Testing and Quality Assurance

- **TDD (Test-Driven Development):** The entire engine was built using TDD principles. Comprehensive test coverage is maintained using **Pest**, including both Unit and Feature tests to ensure reliability across all layers.

## Console Commands / Testing Scenarios

To help introduce and manually test the system's capabilities, custom Artisan commands have been provided:

- **PromocodePlayCommand:** You can execute this command to manually run and test various discount and validation scenarios interactively from the console. 

Run the command using:
```bash
php artisan promocode:play
```
*(Check `php artisan list` or the command's help output for any additional arguments or options).*

## Installation and Initialization

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd promora-marketplace
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Environment Setup:**
   Copy the example `.env` file and generate an application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Setup:**
   Configure your database credentials in the `.env` file. Then, run the migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

5. **Serve the Application:**
   If you are using Laravel Herd, the application will automatically be available at `http://promora-marketplace.test`.
   Alternatively, you can start the local development server:
   ```bash
   php artisan serve
   ```

## Project Directory Structure

Here is a quick overview of the key directories related to the Promocode Engine within this project:

```text
app/
├── Console/Commands/
│   └── PromocodePlayCommand.php      # Command to manually test scenarios
├── Engine/
│   ├── PromocodeEngine.php           # Facade/Singleton entry point
│   ├── OrderContext.php              # Context for the discount application
│   └── Interfaces/
│       └── OrderableInterface.php    # Interface for orderable entities
├── Factories/
│   ├── DiscountFactory.php           # Resolves discount strategies
│   └── ValidationFactory.php         # Builds the validation chain
├── Services/
│   ├── PriceCalculatorService.php    # Core discount logic
│   └── PromocodeValidationService.php # Pre-discount validation logic
├── Validations/                        # Validation classes (CoR)
│   ├── Handlers/
│   │   └── PromocodeHandler.php      # Abstract CoR handler
│   ├── GlobalUsageValidator.php
│   ├── UserUsageValidator.php
│   └── ...
├── Discounts/                          # Discount classes (Template Method)
│   ├── DiscountTemplate.php          # Abstract template method
│   ├── FixedDiscount.php
│   └── PercentageDiscount.php
└── Utils/
    └── Logger.php                    # Singleton logger for debugging

tests/
├── Feature/
│   └── Validations/                  # Feature tests for validations
└── Unit/
    ├── Discount/                     # Unit tests for discount calculations
    ├── Services/                     # Unit tests for services
    └── Validations/                  # Unit tests for specific validators
```
