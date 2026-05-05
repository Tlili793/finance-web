<?php

namespace App\Service\Testing;

use App\Entity\Complaint;
use App\Entity\User;

class ComplaintTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $title = 'Service Delay',
        string $description = 'I experienced a delay in processing my insurance request.',
        string $status = 'OPEN'
    ): Complaint {
        $complaint = new Complaint();
        $complaint->setUser($user);
        $complaint->setTitle($title);
        $complaint->setDescription($description);
        $complaint->setStatus($status);
        
        $this->persist($complaint);
        return $complaint;
    }
}
