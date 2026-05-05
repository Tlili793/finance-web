<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist, priority: 500)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500)]
class BlameableListener
{
    public function __construct(
        private Security $security
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (method_exists($entity, 'setCreatedBy') && $entity->getCreatedBy() === null) {
            $this->invokeProtectedMethod($entity, 'setCreatedBy', $user);
        }

        if (method_exists($entity, 'setUpdatedBy')) {
            $this->invokeProtectedMethod($entity, 'setUpdatedBy', $user);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (method_exists($entity, 'setUpdatedBy')) {
            $this->invokeProtectedMethod($entity, 'setUpdatedBy', $user);
        }
    }

    private function invokeProtectedMethod(object $object, string $method, mixed $value): void
    {
        try {
            $reflection = new \ReflectionMethod($object, $method);
            $reflection->setAccessible(true);
            $reflection->invoke($object, $value);
        } catch (\ReflectionException $e) {
            // Method doesn't exist or other reflection error
        }
    }
}
