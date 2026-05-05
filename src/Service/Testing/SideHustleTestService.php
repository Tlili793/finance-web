<?php

namespace App\Service\Testing;

use App\Entity\SideHustle;
use App\Entity\User;

class SideHustleTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $title = 'Freelance Web Design',
        string $description = 'Building websites for local businesses.',
        string $monthlyIncome = '1200.00'
    ): SideHustle {
        $hustle = new SideHustle();
        $hustle->setUser($user);
        $hustle->setTitle($title);
        $hustle->setDescription($description);
        $hustle->setMonthlyIncome($monthlyIncome);
        
        $this->persist($hustle);
        return $hustle;
    }
}
