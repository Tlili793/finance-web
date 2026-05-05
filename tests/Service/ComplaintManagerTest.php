<?php

namespace App\Tests\Service;

use App\Entity\Complaint;
use App\Service\Manager\ComplaintManager;
use PHPUnit\Framework\TestCase;

class ComplaintManagerTest extends TestCase
{
    private ComplaintManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ComplaintManager();
    }

    private function validComplaint(): Complaint
    {
        $complaint = new Complaint();
        $complaint->setSubject('My insurance claim was not processed in time.');
        $complaint->setComplaintDate(new \DateTime('today'));
        $complaint->setStatus('PENDING');
        return $complaint;
    }

    public function testValidComplaintReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validComplaint()));
    }

    public function testBlankSubjectThrows(): void
    {
        $complaint = $this->validComplaint()->setSubject('   ');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must not be blank/i');
        $this->manager->validate($complaint);
    }

    public function testProfanityInSubjectThrows(): void
    {
        $complaint = $this->validComplaint()->setSubject('This is a badword subject');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/inappropriate language/i');
        $this->manager->validate($complaint);
    }

    public function testNullComplaintDateThrows(): void
    {
        $complaint = new Complaint();
        $complaint->setSubject('Valid subject without profanity');
        $complaint->setStatus('PENDING');
        // complaintDate is null by default

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/date is required/i');
        $this->manager->validate($complaint);
    }

    public function testInvalidStatusThrows(): void
    {
        $complaint = $this->validComplaint()->setStatus('CLOSED');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/PENDING, IN_REVIEW, RESOLVED, REJECTED/i');
        $this->manager->validate($complaint);
    }
}
