<?php

namespace App\Service\Testing;

use App\Entity\Bill;
use App\Entity\User;

class BillTestService extends AbstractEntityTestService
{
    public function create(
        \App\Entity\Budget $budget,
        string $name = 'Electricity Bill',
        string $amount = '85.50',
        string $status = 'UNPAID',
        string $category = 'Utilities'
    ): Bill {
        $bill = new Bill();
        $bill->setBudget($budget);
        $bill->setName($name);
        $bill->setAmount($amount);
        $bill->setStatus($status);
        $bill->setCategory($category);
        $bill->setDueDay(15);
        
        $this->persist($bill);
        return $bill;
    }
}
