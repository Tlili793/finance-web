<?php

namespace App\Service\Testing;

use App\Entity\Loan;
use App\Entity\User;

class LoanTestService extends AbstractEntityTestService
{
    public function create(
        User $borrower,
        string $amount = '10000.00',
        string $interestRate = '5.50',
        int $durationMonths = 24,
        string $status = 'PENDING'
    ): Loan {
        $loan = new Loan();
        $loan->setBorrower($borrower);
        $loan->setAmount($amount);
        $loan->setInterestRate($interestRate);
        $loan->setDurationMonths($durationMonths);
        $loan->setStatus($status);
        
        $this->persist($loan);
        return $loan;
    }
}
