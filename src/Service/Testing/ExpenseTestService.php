<?php

namespace App\Service\Testing;

use App\Entity\Expense;
use App\Entity\User;

class ExpenseTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $title = 'Grocery Shopping',
        string $amount = '120.00',
        string $category = 'Food'
    ): Expense {
        $expense = new Expense();
        $expense->setUser($user);
        $expense->setTitle($title);
        $expense->setAmount($amount);
        $expense->setCategory($category);
        $expense->setDate(new \DateTime());
        
        $this->persist($expense);
        return $expense;
    }
}
