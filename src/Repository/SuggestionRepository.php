<?php

namespace App\Repository;

use App\Entity\Suggestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Suggestion>
 *
 * @method Suggestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Suggestion|null findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method Suggestion[]    findAll()
 * @method Suggestion[]    findBy(array<string, mixed> $criteria, array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class SuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suggestion::class);
    }

    /**
     * Optimized fetch for Suggestion with its User and Profile in a single query.
     */
    public function findWithDetails(int $id): ?Suggestion
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->addSelect('u')
            ->leftJoin('s.profile', 'p')
            ->addSelect('p')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
