<?php

namespace App\Service\Testing;

use App\Entity\Budget;
use App\Entity\User;

class BudgetTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $category = 'Food',
        string $limitAmount = '1000.00'
    ): Budget {
        $budget = new Budget();
        $budget->setUser($user);
        $budget->setCategory($category);
        $budget->setLimitAmount($limitAmount);
        $budget->setStartDate(new \DateTime('first day of this month'));
        $budget->setEndDate(new \DateTime('last day of this month'));
        
        $this->persist($budget);
        return $budget;
    }
}
