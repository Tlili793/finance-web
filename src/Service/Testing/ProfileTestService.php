<?php

namespace App\Service\Testing;

use App\Entity\Profile;
use App\Entity\User;

class ProfileTestService extends AbstractEntityTestService
{
    public function create(
        User $user,
        string $firstName = 'John',
        string $lastName = 'Doe',
        string $address = '123 Main St'
    ): Profile {
        $profile = new Profile();
        $profile->setUser($user);
        $profile->setFirstName($firstName);
        $profile->setLastName($lastName);
        $profile->setAddress($address);
        
        $this->persist($profile);
        return $profile;
    }
}
