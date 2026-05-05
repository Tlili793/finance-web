<?php

namespace App\Service\Manager;

use App\Entity\Budget;

/**
 * Business Rules for Budget:
 *  1. Name must be between 3 and 150 characters.
 *  2. Amount must be a positive number greater than 0.
 *  3. End date must be after start date.
 *  4. Budget must be linked to a user.
 */
class BudgetManager
{
    public function validate(Budget $budget): bool
    {
        $name = trim($budget->getName() ?? '');
        if (strlen($name) < 3 || strlen($name) > 150) {
            throw new \InvalidArgumentException('Budget name must be between 3 and 150 characters.');
        }

        $amount = (float) ($budget->getAmount() ?? '0');
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Budget amount must be greater than 0.');
        }

        if ($budget->getUser() === null) {
            throw new \InvalidArgumentException('Budget must be linked to a user.');
        }

        $start = $budget->getStartDate();
        $end = $budget->getEndDate();
        if ($start !== null && $end !== null && $end <= $start) {
            throw new \InvalidArgumentException('Budget end date must be after the start date.');
        }

        return true;
    }
}
