<?php

namespace App\Service\Manager;

use App\Entity\Transaction;

/**
 * Business Rules for Transaction:
 *  1. Amount must be a positive number greater than 0.
 *  2. Type must not be blank.
 *  3. Transaction must be linked to a user.
 */
class TransactionManager
{
    public function validate(Transaction $transaction): bool
    {
        $amount = (float) ($transaction->getAmount() ?? '0');
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transaction amount must be greater than 0.');
        }

        if (empty(trim($transaction->getType() ?? ''))) {
            throw new \InvalidArgumentException('Transaction type must not be blank.');
        }

        if ($transaction->getUser() === null) {
            throw new \InvalidArgumentException('Transaction must be linked to a user.');
        }

        return true;
    }
}
