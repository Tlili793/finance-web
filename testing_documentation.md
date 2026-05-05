# Business Logic & Unit Testing Documentation

This project uses a dedicated "Business Manager" layer to isolate core domain logic from framework dependencies (controllers, repositories, and database). This ensures that business rules are strictly enforced and easily testable.

## 🏗️ Architecture Overview

Each core entity has a corresponding **Manager** service located in `src/Service/Manager/`.

- **Entity**: Data structure with Doctrine mapping and validation constraints.
- **Manager**: Pure PHP service containing a `validate()` method to enforce business rules manually.
- **Unit Test**: Isolated PHPUnit tests in `tests/Service/` that verify the Manager's logic without a database.

---

## 🚀 Running the Tests

### 1. Run all isolated unit tests
To run only the business manager tests with a clean output:
```powershell
php bin/phpunit tests/Service/ --testdox
```

### 2. Run a specific test
```powershell
php bin/phpunit tests/Service/BillManagerTest.php --testdox
```

### 3. Run all project tests (Unit + Integration)
```powershell
php bin/phpunit
```

---

## 🛠️ How to Use

### 1. Adding a New Business Rule
If you need to add a new rule (e.g., "A budget cannot exceed 10 years duration"):

1. Open the manager class (e.g., `src/Service/Manager/BudgetManager.php`).
2. Add the logic inside the `validate()` method:
   ```php
   if ($budget->getDurationInYears() > 10) {
       throw new \InvalidArgumentException('Budget duration cannot exceed 10 years.');
   }
   ```

### 2. Adding a New Test Case
Every time you add a rule, you **must** add a corresponding test in `tests/Service/`:

1. Open the test class (e.g., `tests/Service/BudgetManagerTest.php`).
2. Add a failing scenario:
   ```php
   public function testDurationTooLongThrows(): void
   {
       $budget = $this->validBudget();
       $budget->setStartDate(new \DateTime('2024-01-01'));
       $budget->setEndDate(new \DateTime('2040-01-01')); // 16 years
       
       $this->expectException(\InvalidArgumentException::class);
       $this->expectExceptionMessageMatches('/cannot exceed 10 years/i');
       
       $this->manager->validate($budget);
   }
   ```

---

## 💡 Best Practices

1. **Strict Isolation**: 
   - Never use the database in these tests.
   - Use `$this->createMock(Entity::class)` for related entities to avoid cascading setup.
   
2. **Descriptive Failures**: 
   - Always provide a clear message in `InvalidArgumentException`.
   - Use `expectExceptionMessageMatches()` in tests to ensure the *correct* rule triggered the failure.

3. **Validation vs. Business Rules**: 
   - Symfony Constraints (`#[Assert\NotBlank]`) handle basic form/API validation.
   - Manager Rules handle complex domain logic, cross-field dependencies, and state transitions.

4. **Integration**: 
   - Use these Managers in your Controllers or Event Listeners before persisting data to ensure the entity is always in a valid state according to business requirements.

---

## 📊 Available Managers

| Entity | Manager Class | Test Class |
|---|---|---|
| **Bill** | `BillManager` | `BillManagerTest` |
| **Budget** | `BudgetManager` | `BudgetManagerTest` |
| **Complaint** | `ComplaintManager` | `ComplaintManagerTest` |
| **ContractRequest** | `ContractRequestManager` | `ContractRequestManagerTest` |
| **Expense** | `ExpenseManager` | `ExpenseManagerTest` |
| **InsurancePackage** | `InsurancePackageManager` | `InsurancePackageManagerTest` |
| **InsuredAsset** | `InsuredAssetManager` | `InsuredAssetManagerTest` |
| **InsuredContract** | `InsuredContractManager` | `InsuredContractManagerTest` |
| **Loan** | `LoanManager` | `LoanManagerTest` |
| **Profile** | `ProfileManager` | `ProfileManagerTest` |
| **Repayment** | `RepaymentManager` | `RepaymentManagerTest` |
| **Role** | `RoleManager` | `RoleManagerTest` |
| **Suggestion** | `SuggestionManager` | `SuggestionManagerTest` |
| **User** | `UserManager` | `UserManagerTest` |
