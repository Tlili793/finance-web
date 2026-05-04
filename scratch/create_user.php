<?php

require 'vendor/autoload.php';

use App\Kernel;
use App\Entity\User;
use App\Entity\Role;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load('.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$hasher = $container->get('security.user_password_hasher');

$role = $em->getRepository(Role::class)->findOneBy(['roleName' => 'ROLE_ADMIN']);
if (!$role) {
    $role = new Role();
    $role->setRoleName('ROLE_ADMIN');
    $role->setPermissions('All permissions');
    $em->persist($role);
}

$user = new User();
$user->setName('Admin User');
$user->setEmail('admin@example.com');
$user->setPassword($hasher->hashPassword($user, 'password123'));
$user->setRole($role);
$user->setIsVerified(true);

$em->persist($user);
$em->flush();

echo "User created: admin@example.com / password123\n";
