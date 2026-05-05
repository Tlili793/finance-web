<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array<string, mixed> $criteria, array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
/**
     * Search users by name OR email (case-insensitive LIKE).
     * Real DQL business query — grille criterion 3.
     *
     * @return User[]
     */
    public function searchByNameOrEmail(string $q): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.name LIKE :q OR u.email.address LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count verified vs unverified — for admin dashboard stats.
     * 
     * @return array<int, array{isVerified: bool, total: int}>
     */
    public function countByVerified(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.isVerified, COUNT(u.id) as total')
            ->groupBy('u.isVerified')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recent registrations in last N days — for admin dashboard.
     *
     * @return User[]
     */
    public function findRecentUsers(int $days = 7): array
    {
        $since = new \DateTime('-' . $days . ' days');
        return $this->createQueryBuilder('u')
            ->where('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * AI-powered search — applies structured filters returned by Groq.
     *
     * @param array<string, mixed> $filters
     * @return User[]
     */
    public function findByAiFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('u');

        if (isset($filters['isActive'])) {
            $qb->andWhere('u.isActive = :isActive')
               ->setParameter('isActive', (bool) $filters['isActive']);
        }

        if (isset($filters['isVerified'])) {
            $qb->andWhere('u.isVerified = :isVerified')
               ->setParameter('isVerified', (bool) $filters['isVerified']);
        }

        if (isset($filters['googleAccount'])) {
            $qb->andWhere('u.googleAccount = :googleAccount')
               ->setParameter('googleAccount', (bool) $filters['googleAccount']);
        }

        if (isset($filters['createdAfter'])) {
            $qb->andWhere('u.createdAt >= :createdAfter')
               ->setParameter('createdAfter', new \DateTime($filters['createdAfter']));
        }

        if (isset($filters['createdBefore'])) {
            $qb->andWhere('u.createdAt <= :createdBefore')
               ->setParameter('createdBefore', new \DateTime($filters['createdBefore']));
        }

        if (isset($filters['lastLoginBefore'])) {
            $qb->andWhere('u.lastLogin <= :lastLoginBefore OR u.lastLogin IS NULL')
               ->setParameter('lastLoginBefore', new \DateTime($filters['lastLoginBefore']));
        }

        if (isset($filters['lastLoginAfter'])) {
            $qb->andWhere('u.lastLogin >= :lastLoginAfter')
               ->setParameter('lastLoginAfter', new \DateTime($filters['lastLoginAfter']));
        }

        if (isset($filters['keyword'])) {
            $qb->andWhere('u.name LIKE :keyword OR u.email.address LIKE :keyword')
               ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        $orderField = $filters['orderBy'] ?? 'createdAt';
        $orderDir   = $filters['orderDir'] ?? 'DESC';
        $allowedFields = ['createdAt', 'lastLogin', 'name', 'id'];
        if (!in_array($orderField, $allowedFields)) {
            $orderField = 'createdAt';
        }

        $qb->orderBy('u.' . $orderField, $orderDir === 'ASC' ? 'ASC' : 'DESC');

        return $qb->getQuery()->getResult();
    }
}
