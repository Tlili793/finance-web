<?php

namespace App\Service\Testing;

use App\Entity\Complaint;
use App\Entity\User;

class ComplaintTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $subject = 'Service Delay',
        string $status = 'PENDING'
    ): Complaint {
        $complaint = new Complaint();
        $complaint->setUser($user);
        $complaint->setSubject($subject);
        $complaint->setStatus($status);
        $complaint->setComplaintDate(new \DateTime());
        
        $this->persist($complaint);
        return $complaint;
    }
}
