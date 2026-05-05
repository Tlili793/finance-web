<?php

namespace App\Service\Testing;

use App\Entity\Expense;
use App\Entity\User;

class ExpenseTestService extends AbstractEntityTestService
{
    public function create(
        \App\Entity\Budget $budget,
        string $amount = '120.00',
        string $category = 'Food'
    ): Expense {
        $expense = new Expense();
        $expense->setBudget($budget);
        $expense->setAmount($amount);
        $expense->setCategory($category);
        $expense->setExpenseDate(new \DateTime());
        
        $this->persist($expense);
        return $expense;
    }
}
