<?php

namespace App\Service\Testing;

use App\Entity\Role;

class RoleTestService extends AbstractEntityTestService
{
    public function create(string $name = 'Administrator', string $code = 'ROLE_ADMIN'): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setCode($code);
        
        $this->persist($role);
        return $role;
    }

    public function findOrCreate(string $code = 'ROLE_ADMIN', string $name = 'Administrator'): Role
    {
        $role = $this->em->getRepository(Role::class)->findOneBy(['code' => $code]);
        if (!$role) {
            $role = $this->create($name, $code);
        }
        return $role;
    }
}
