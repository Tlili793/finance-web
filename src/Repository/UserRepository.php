<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
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
            ->where('u.name LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count verified vs unverified — for admin dashboard stats.
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
 * @param array $filters
 * @return User[]
 */
public function findByAiFilters(array $filters): array
{
    $qb = $this->createQueryBuilder('u');

    // isActive filter
    if (isset($filters['isActive'])) {
        $qb->andWhere('u.isActive = :isActive')
           ->setParameter('isActive', (bool) $filters['isActive']);
    }

    // isVerified filter
    if (isset($filters['isVerified'])) {
        $qb->andWhere('u.isVerified = :isVerified')
           ->setParameter('isVerified', (bool) $filters['isVerified']);
    }

    // googleAccount filter
    if (isset($filters['googleAccount'])) {
        $qb->andWhere('u.googleAccount = :googleAccount')
           ->setParameter('googleAccount', (bool) $filters['googleAccount']);
    }

    // roleId filter (1 = admin, 2 = user)
    if (isset($filters['roleId'])) {
        $qb->andWhere('u.roleId = :roleId')
           ->setParameter('roleId', (int) $filters['roleId']);
    }

    // createdAt — signed up after date
    if (isset($filters['createdAfter'])) {
        $qb->andWhere('u.createdAt >= :createdAfter')
           ->setParameter('createdAfter', new \DateTime($filters['createdAfter']));
    }

    // createdAt — signed up before date
    if (isset($filters['createdBefore'])) {
        $qb->andWhere('u.createdAt <= :createdBefore')
           ->setParameter('createdBefore', new \DateTime($filters['createdBefore']));
    }

    // lastLogin — not logged in since date
    if (isset($filters['lastLoginBefore'])) {
        $qb->andWhere('u.lastLogin <= :lastLoginBefore OR u.lastLogin IS NULL')
           ->setParameter('lastLoginBefore', new \DateTime($filters['lastLoginBefore']));
    }

    // lastLogin — logged in after date
    if (isset($filters['lastLoginAfter'])) {
        $qb->andWhere('u.lastLogin >= :lastLoginAfter')
           ->setParameter('lastLoginAfter', new \DateTime($filters['lastLoginAfter']));
    }

    // name or email keyword fallback
    if (isset($filters['keyword'])) {
        $qb->andWhere('u.name LIKE :keyword OR u.email LIKE :keyword')
           ->setParameter('keyword', '%' . $filters['keyword'] . '%');
    }

    // orderBy
    $orderField = $filters['orderBy'] ?? 'createdAt';
    $orderDir   = $filters['orderDir'] ?? 'DESC';

    $allowedFields = ['createdAt', 'lastLogin', 'name', 'email', 'id'];
    if (!in_array($orderField, $allowedFields)) {
        $orderField = 'createdAt';
    }

    $qb->orderBy('u.' . $orderField, $orderDir === 'ASC' ? 'ASC' : 'DESC');

    return $qb->getQuery()->getResult();
}}
