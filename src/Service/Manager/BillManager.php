<?php

namespace App\Service\Manager;

use App\Entity\Bill;

/**
 * Business Rules for Bill:
 *  1. Name must not be blank and must be at least 2 characters.
 *  2. Amount must be a positive number greater than 0.
 *  3. Amount must be less than 1,000,000.
 *  4. DueDay must be between 1 and 31.
 *  5. Frequency must be one of: MONTHLY, WEEKLY, YEARLY.
 */
class BillManager
{
    private const VALID_FREQUENCIES = ['MONTHLY', 'WEEKLY', 'YEARLY'];
    private const MAX_AMOUNT = 1_000_000;

    public function validate(Bill $bill): bool
    {
        $name = trim($bill->getName() ?? '');
        if (strlen($name) < 2) {
            throw new \InvalidArgumentException('Bill name must be at least 2 characters long.');
        }

        $amount = (float) ($bill->getAmount() ?? '0');
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Bill amount must be greater than 0.');
        }

        if ($amount >= self::MAX_AMOUNT) {
            throw new \InvalidArgumentException('Bill amount must be less than 1,000,000.');
        }

        $dueDay = $bill->getDueDay();
        if ($dueDay < 1 || $dueDay > 31) {
            throw new \InvalidArgumentException('Due day must be between 1 and 31.');
        }

        if (!in_array($bill->getFrequency(), self::VALID_FREQUENCIES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Frequency must be one of: %s.', implode(', ', self::VALID_FREQUENCIES))
            );
        }

        return true;
    }
}
