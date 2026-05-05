<?php

namespace App\Service\Testing;

use App\Entity\Loan;
use App\Entity\Repayment;

class RepaymentTestService extends AbstractEntityTestService
{
    public function create(
        Loan $loan,
        string $amount = '450.00',
        string $status = 'PENDING'
    ): Repayment {
        $repayment = new Repayment();
        $repayment->setLoan($loan);
        $repayment->setAmount($amount);
        $repayment->setPaymentDate(new \DateTime('+1 month'));
        $repayment->setStatus($status);
        
        $this->persist($repayment);
        return $repayment;
    }
}
