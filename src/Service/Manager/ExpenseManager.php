<?php

namespace App\Service\Manager;

use App\Entity\Expense;

/**
 * Business Rules for Expense:
 *  1. Amount must be a positive number greater than 0.
 *  2. Expense date must not be null.
 *  3. Expense date must not be in the future.
 */
class ExpenseManager
{
    public function validate(Expense $expense): bool
    {
        $amount = (float) ($expense->getAmount() ?? '0');
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Expense amount must be greater than 0.');
        }

        $date = $expense->getExpenseDate();
        if ($date === null) {
            throw new \InvalidArgumentException('Expense date is required.');
        }

        if ($date > new \DateTime('today')) {
            throw new \InvalidArgumentException('Expense date cannot be in the future.');
        }

        return true;
    }
}
