<?php

namespace App\Tests\Service;

use App\Entity\Profile;
use App\Entity\User;
use App\Service\Manager\ProfileManager;
use PHPUnit\Framework\TestCase;

class ProfileManagerTest extends TestCase
{
    private ProfileManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ProfileManager();
    }

    private function validProfile(): Profile
    {
        $user = $this->createMock(User::class);
        $profile = new Profile();
        $profile->setDescription('This is a valid profile description that is at least 10 characters long.');
        $profile->setUser($user);
        return $profile;
    }

    public function testValidProfileReturnsTrue(): void
    {
        $this->assertTrue($this->manager->validate($this->validProfile()));
    }

    public function testDescriptionTooShortThrows(): void
    {
        $profile = $this->validProfile();
        $profile->setDescription('Too short');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/between 10 and 2000/i');
        $this->manager->validate($profile);
    }

    public function testMissingUserThrows(): void
    {
        $profile = $this->validProfile();
        $profile->setUser(null);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/linked to a user/i');
        $this->manager->validate($profile);
    }
}
