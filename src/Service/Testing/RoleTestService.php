<?php

namespace App\Service\Testing;

use App\Entity\Role;

class RoleTestService extends AbstractEntityTestService
{
    public function create(string $roleName = 'ROLE_ADMIN'): Role
    {
        $role = new Role();
        $role->setRoleName($roleName);
        
        $this->persist($role);
        return $role;
    }

    public function findOrCreate(string $roleName = 'ROLE_ADMIN'): Role
    {
        $role = $this->em->getRepository(Role::class)->findOneBy(['roleName' => $roleName]);
        if (!$role) {
            $role = $this->create($roleName);
        }
        return $role;
    }
}
