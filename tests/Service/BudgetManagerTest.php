<?php

namespace App\Tests\Service;

use App\Entity\Budget;
use App\Entity\User;
use App\Service\Manager\BudgetManager;
use PHPUnit\Framework\TestCase;

class BudgetManagerTest extends TestCase
{
    private BudgetManager $manager;

    protected function setUp(): void
    {
        $this->manager = new BudgetManager();
    }

    private function validBudget(): Budget
    {
        $user = $this->createMock(User::class);
        $budget = new Budget();
        $budget->setName('Monthly Groceries');
        $budget->setAmount('1200.00');
        $budget->setUser($user);
        $budget->setStartDate(new \DateTime('first day of this month'));
        $budget->setEndDate(new \DateTime('last day of this month'));
        return $budget;
    }

    public function testValidBudgetReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validBudget()));
    }

    public function testNameTooShortThrows(): void
    {
        $budget = $this->validBudget();
        $budget->setName('Ab');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 3 and 150/i');
        $this->manager->validate($budget);
    }

    public function testZeroAmountThrows(): void
    {
        $budget = $this->validBudget();
        $budget->setAmount('0.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($budget);
    }

    public function testMissingUserThrows(): void
    {
        $budget = $this->validBudget();
        $budget->setUser(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/linked to a user/i');
        $this->manager->validate($budget);
    }

    public function testInvalidDateRangeThrows(): void
    {
        $budget = $this->validBudget();
        $budget->setStartDate(new \DateTime('today'));
        $budget->setEndDate(new \DateTime('yesterday'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/after the start date/i');
        $this->manager->validate($budget);
    }
}
