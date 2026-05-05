<?php

namespace App\Tests\Service;

use App\Entity\Bill;
use App\Service\Manager\BillManager;
use PHPUnit\Framework\TestCase;

class BillManagerTest extends TestCase
{
    private BillManager $manager;

    protected function setUp(): void
    {
        $this->manager = new BillManager();
    }

    private function validBill(): Bill
    {
        $bill = new Bill();
        $bill->setName('Electricity');
        $bill->setAmount('85.50');
        $bill->setDueDay(15);
        $bill->setFrequency('MONTHLY');
        return $bill;
    }

    public function testValidBillReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validBill()));
    }

    public function testNameTooShortThrows(): void
    {
        $bill = $this->validBill()->setName('X');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/at least 2/i');
        $this->manager->validate($bill);
    }

    public function testZeroAmountThrows(): void
    {
        $bill = $this->validBill()->setAmount('0');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($bill);
    }

    public function testNegativeAmountThrows(): void
    {
        $bill = $this->validBill()->setAmount('-50');
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->validate($bill);
    }

    public function testExcessiveAmountThrows(): void
    {
        $bill = $this->validBill()->setAmount('1000000');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/less than 1,000,000/i');
        $this->manager->validate($bill);
    }

    public function testInvalidDueDayThrows(): void
    {
        $bill = $this->validBill()->setDueDay(32);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 1 and 31/i');
        $this->manager->validate($bill);
    }

    public function testInvalidFrequencyThrows(): void
    {
        $bill = $this->validBill()->setFrequency('DAILY');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/MONTHLY, WEEKLY, YEARLY/i');
        $this->manager->validate($bill);
    }
}
