# 🧪 Unit & Integration Testing Documentation

This document outlines the testing strategy, architecture, and best practices for the FinanceApp project.

---

## 🏗️ Testing Architecture

The project employs a multi-layered testing strategy:

1.  **Isolated Unit Tests (`tests/Service/`)**: Tests core business logic in the `Manager` layer without database or container dependencies.
2.  **Kernel Integration Tests (`tests/`)**: Tests entities and repository interactions using the Symfony Kernel and a test database.
3.  **Controller/Functional Tests (`tests/Controller/`)**: Tests full HTTP request/response cycles and UI interactions.

---

## 🛠️ The "Test Service" Factory Pattern

To maintain clean and DRY (Don't Repeat Yourself) tests, we use a custom Factory pattern located in `src/Service/Testing/`.

### How it works:
Each entity has a corresponding `*TestService` (e.g., `BudgetTestService`) that extends `AbstractEntityTestService`. These services provide a consistent way to create valid entities for testing purposes.

**Example usage in a test:**
```php
public function testBudgetCalculation(): void
{
    // The TestService handles boilerplate like setting dates, users, and persisting
    $budget = $this->budgetTestService->create($user, 'Vacation', 'Travel', '5000.00');
    
    $this->assertEquals('5000.00', $budget->getAmount());
}
```

---

## 🎯 Testing Business Logic (Manager Layer)

Business rules are isolated in `src/Service/Manager/`. These classes are **pure PHP** and should be tested using isolated Unit Tests.

### Best Practices for Manager Tests:
- **No Database**: Always mock dependencies.
- **Atomic**: Each test should verify exactly one business rule.
- **Fail First**: Write tests that expect specific exceptions (e.g., `InvalidArgumentException`).

**Example:**
```php
public function testLoanAmountCannotBeNegative(): void
{
    $loan = new Loan();
    $loan->setAmount('-100.00');
    
    $this->expectException(\InvalidArgumentException::class);
    $this->manager->validate($loan);
}
```

---

## 💾 Integration Testing (Kernel)

For tests that require the database (like Repository methods or Entity lifecycle events), we use `KernelTestCase`.

### Database Handling:
Tests are executed within a **database transaction** that is rolled back after each test, ensuring a clean state for every execution.

```bash
# Set up test database
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:migrations:migrate
```

---

## 🚀 Running Tests

### Run all tests
```bash
php bin/phpunit
```

### Run only Unit Tests (Fast)
```bash
php bin/phpunit tests/Service/
```

### Run with TestDox (Human-readable output)
```bash
php bin/phpunit --testdox
```

---

## 📈 Code Quality & Analysis

Beyond PHPUnit, we use these tools to ensure code health:

- **PHPStan**: Static analysis to catch type errors before they happen.
  ```bash
  vendor/bin/phpstan analyse src
  ```
- **Strict Typing**: All test services and managers must use `declare(strict_types=1);`.

---

## 👥 Contributors
- Mohamed Wassim Tlili
- Fathi Mejri
- Hanin Limam
- Salma Boubakri
- Yasmine Ben Abedelkader
