<?php

namespace App\Tests\Service;

use App\Entity\Expense;
use App\Service\Manager\ExpenseManager;
use PHPUnit\Framework\TestCase;

class ExpenseManagerTest extends TestCase
{
    private ExpenseManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ExpenseManager();
    }

    private function validExpense(): Expense
    {
        $expense = new Expense();
        $expense->setAmount('120.00');
        $expense->setExpenseDate(new \DateTime('yesterday'));
        return $expense;
    }

    public function testValidExpenseReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validExpense()));
    }

    public function testZeroAmountThrows(): void
    {
        $expense = $this->validExpense()->setAmount('0');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($expense);
    }

    public function testNegativeAmountThrows(): void
    {
        $expense = $this->validExpense()->setAmount('-10.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->validate($expense);
    }

    public function testNullDateThrows(): void
    {
        $expense = new Expense();
        $expense->setAmount('50.00');
        // expenseDate is null by default
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/date is required/i');
        $this->manager->validate($expense);
    }

    public function testFutureDateThrows(): void
    {
        $expense = $this->validExpense()->setExpenseDate(new \DateTime('+1 day'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be in the future/i');
        $this->manager->validate($expense);
    }
}
