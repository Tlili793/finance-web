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
        string $status = 'active'
    ): Loan {
        $loan = new Loan();
        $loan->setUser($borrower);
        $loan->setAmount($amount);
        $loan->setInterestRate($interestRate);
        $loan->setStartDate(new \DateTime());
        $loan->setEndDate(new \DateTime('+2 years'));
        $loan->setStatus($status);
        
        $this->persist($loan);
        return $loan;
    }
}
