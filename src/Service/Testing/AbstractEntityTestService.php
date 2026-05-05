<?php

namespace App\Service\Testing;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Base class for all entity testing services.
 * Provides shared access to EntityManager and persistence helpers.
 */
abstract class AbstractEntityTestService
{
    public function __construct(
        protected EntityManagerInterface $em
    ) {}

    public function flush(): void
    {
        $this->em->flush();
    }

    public function clear(): void
    {
        $this->em->clear();
    }

    public function remove(object $entity, bool $flush = true): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
    
    public function persist(object $entity, bool $flush = true): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }
}
