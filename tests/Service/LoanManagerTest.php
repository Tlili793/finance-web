<?php

namespace App\Tests\Service;

use App\Entity\Loan;
use App\Service\Manager\LoanManager;
use PHPUnit\Framework\TestCase;

class LoanManagerTest extends TestCase
{
    private LoanManager $manager;

    protected function setUp(): void
    {
        $this->manager = new LoanManager();
    }

    private function validLoan(): Loan
    {
        $loan = new Loan();
        $loan->setAmount('10000.00');
        $loan->setInterestRate('5.50');
        $loan->setStartDate(new \DateTime('2024-01-01'));
        $loan->setEndDate(new \DateTime('2026-01-01'));
        $loan->setStatus('active');
        return $loan;
    }

    public function testValidLoanReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validLoan()));
    }

    public function testZeroAmountThrows(): void
    {
        $loan = $this->validLoan()->setAmount('0');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($loan);
    }

    public function testNegativeAmountThrows(): void
    {
        $loan = $this->validLoan()->setAmount('-5000');
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->validate($loan);
    }

    public function testBlankInterestRateThrows(): void
    {
        $loan = $this->validLoan()->setInterestRate('');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/interest rate is required/i');
        $this->manager->validate($loan);
    }

    public function testExcessiveInterestRateThrows(): void
    {
        $loan = $this->validLoan()->setInterestRate('150.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 0.*and 100/i');
        $this->manager->validate($loan);
    }

    public function testEndDateBeforeStartDateThrows(): void
    {
        $loan = $this->validLoan();
        $loan->setEndDate(new \DateTime('2023-01-01')); // before 2024-01-01
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/end date must be after/i');
        $this->manager->validate($loan);
    }

    public function testInvalidStatusThrows(): void
    {
        $loan = $this->validLoan()->setStatus('pending');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/active, closed, defaulted/i');
        $this->manager->validate($loan);
    }
}
