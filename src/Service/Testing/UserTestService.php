<?php

namespace App\Service\Testing;

use App\Entity\User;

class UserTestService extends AbstractEntityTestService
{
    /**
     * Creates and persists a standard User for testing.
     */
    public function create(
        string $email = 'test@example.com',
        string $name = 'Test User',
        string $password = 'password123',
        bool $isActive = true,
        array $roles = ['ROLE_USER']
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPasswordHash(password_hash($password, PASSWORD_BCRYPT));
        $user->setIsActive($isActive);
        
        // Since getRoles() is hardcoded in User entity to return ['ROLE_USER'],
        // we assume roles are managed elsewhere or this is enough for simple tests.
        
        $this->persist($user);
        return $user;
    }

    /**
     * Finds a user by email or creates a new one if not found.
     */
    public function findOrCreate(string $email = 'test@example.com', string $name = 'Test User'): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email.address' => $email]);
        if (!$user) {
            $user = $this->create($email, $name);
        }
        return $user;
    }
}
