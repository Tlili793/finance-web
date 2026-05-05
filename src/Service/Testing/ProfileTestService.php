<?php

namespace App\Service\Testing;

use App\Entity\Profile;
use App\Entity\User;

class ProfileTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $description = 'This is a sample profile description with enough characters.'
    ): Profile {
        $profile = new Profile();
        $profile->setUser($user);
        $profile->setDescription($description);
        
        $this->persist($profile);
        return $profile;
    }
}
