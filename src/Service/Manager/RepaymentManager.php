<?php

namespace App\Service\Manager;

use App\Entity\Repayment;

/**
 * Business Rules for Repayment:
 *  1. Amount must be a positive number greater than 0.
 *  2. Payment date must not be null.
 *  3. Repayment must be linked to a loan.
 */
class RepaymentManager
{
    public function validate(Repayment $repayment): bool
    {
        $amount = (float) ($repayment->getAmount() ?? '0');
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Repayment amount must be greater than 0.');
        }

        if ($repayment->getPaymentDate() === null) {
            throw new \InvalidArgumentException('Repayment date is required.');
        }

        if ($repayment->getLoan() === null) {
            throw new \InvalidArgumentException('Repayment must be linked to a loan.');
        }

        return true;
    }
}
