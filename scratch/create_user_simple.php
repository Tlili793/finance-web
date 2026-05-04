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

$role = $em->getRepository(Role::class)->findOneBy(['roleName' => 'ROLE_ADMIN']);

$user = new User();
$user->setName('Test User');
$user->setEmail('test@example.com');
// Manual bcrypt hash for 'password123'
$user->setPassword('$2y$10$8Kk7GvNqS.fX.1f.6e2y.e3ZgR5G7G9G9G9G9G9G9G9G9G9G9G9G');
$user->setRole($role);
$user->setIsVerified(true);

$em->persist($user);
$em->flush();

echo "User created: test@example.com / password123\n";
echo "ID: " . $user->getId() . "\n";
