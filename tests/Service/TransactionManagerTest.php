<?php

namespace App\Tests\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Service\Manager\TransactionManager;
use PHPUnit\Framework\TestCase;

class TransactionManagerTest extends TestCase
{
    private TransactionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TransactionManager();
    }

    private function validTransaction(): Transaction
    {
        $user = $this->createMock(User::class);
        $transaction = new Transaction();
        $transaction->setAmount('250.00');
        $transaction->setType('EXPENSE');
        $transaction->setUser($user);
        return $transaction;
    }

    public function testValidTransactionReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validTransaction()));
    }

    public function testZeroAmountThrows(): void
    {
        $transaction = $this->validTransaction();
        $transaction->setAmount('0.00');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/greater than 0/i');
        $this->manager->validate($transaction);
    }

    public function testBlankTypeThrows(): void
    {
        $transaction = $this->validTransaction();
        $transaction->setType('   ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/type must not be blank/i');
        $this->manager->validate($transaction);
    }

    public function testMissingUserThrows(): void
    {
        $transaction = $this->validTransaction();
        $transaction->setUser(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/linked to a user/i');
        $this->manager->validate($transaction);
    }
}
