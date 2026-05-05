<?php

namespace App\Service\Testing;

use App\Entity\Bill;
use App\Entity\User;

class BillTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $title = 'Electricity Bill',
        string $amount = '85.50',
        string $status = 'UNPAID',
        string $category = 'Utilities'
    ): Bill {
        $bill = new Bill();
        $bill->setUser($user);
        $bill->setTitle($title);
        $bill->setAmount($amount);
        $bill->setStatus($status);
        $bill->setCategory($category);
        $bill->setDueDate(new \DateTime('+15 days'));
        
        $this->persist($bill);
        return $bill;
    }
}
