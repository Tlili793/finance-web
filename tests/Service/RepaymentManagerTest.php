<?php

namespace App\Tests\Service;

use App\Entity\Loan;
use App\Entity\Repayment;
use App\Service\Manager\RepaymentManager;
use PHPUnit\Framework\TestCase;

class RepaymentManagerTest extends TestCase
{
    private RepaymentManager $manager;

    protected function setUp(): void
    {
        $this->manager = new RepaymentManager();
    }

    private function validRepayment(): Repayment
    {
        $loan = $this->createMock(Loan::class);
        $repayment = new Repayment();
        $repayment->setAmount('500.00');
        $repayment->setPaymentDate(new \DateTime('today'));
        $repayment->setLoan($loan);
        return $repayment;
    }

    public function testValidRepaymentReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validRepayment()));
    }

    public function testZeroAmountThrows(): void
    {
        $repayment = $this->validRepayment();
        $repayment->setAmount('0.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($repayment);
    }

    public function testNullDateThrows(): void
    {
        // Create a repayment without setting the date (it will be null by default)
        $loan = $this->createMock(Loan::class);
        $repayment = new Repayment();
        $repayment->setAmount('500.00');
        $repayment->setLoan($loan);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/date is required/i');
        $this->manager->validate($repayment);
    }

    public function testMissingLoanThrows(): void
    {
        $repayment = $this->validRepayment();
        $repayment->setLoan(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/linked to a loan/i');
        $this->manager->validate($repayment);
    }
}
