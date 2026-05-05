<?php

namespace App\Service\Testing;

use App\Entity\Transaction;
use App\Entity\User;

class TransactionTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $title = 'Salary Deposit',
        string $amount = '3500.00',
        string $type = 'INCOME',
        string $status = 'COMPLETED'
    ): Transaction {
        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setTitle($title);
        $transaction->setAmount($amount);
        $transaction->setType($type);
        $transaction->setStatus($status);
        $transaction->setDate(new \DateTime());
        
        $this->persist($transaction);
        return $transaction;
    }
}
