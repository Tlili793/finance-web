<?php

namespace App\Service\Manager;

use App\Entity\Loan;

/**
 * Business Rules for Loan:
 *  1. Amount must be a positive number greater than 0.
 *  2. Interest rate must not be blank.
 *  3. Interest rate must be between 0 (exclusive) and 100 (inclusive).
 *  4. End date must be after start date.
 *  5. Status must be one of: active, closed, defaulted.
 */
class LoanManager
{
    private const VALID_STATUSES = ['active', 'closed', 'defaulted'];

    public function validate(Loan $loan): bool
    {
        $amount = (float) ($loan->getAmount() ?? '0');
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Loan amount must be greater than 0.');
        }

        $rate = $loan->getInterestRate();
        if ($rate === null || trim($rate) === '') {
            throw new \InvalidArgumentException('Interest rate is required.');
        }

        $rateValue = (float) $rate;
        if ($rateValue <= 0 || $rateValue > 100) {
            throw new \InvalidArgumentException('Interest rate must be between 0 (exclusive) and 100.');
        }

        $start = $loan->getStartDate();
        $end   = $loan->getEndDate();
        if ($start !== null && $end !== null && $end <= $start) {
            throw new \InvalidArgumentException('Loan end date must be after the start date.');
        }

        if (!in_array($loan->getStatus(), self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Status must be one of: %s.', implode(', ', self::VALID_STATUSES))
            );
        }

        return true;
    }
}
