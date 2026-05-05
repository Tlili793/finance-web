<?php
namespace App\Repository;

use App\Entity\Repayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Repayment>
 *
 * @method Repayment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Repayment|null findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method Repayment[]    findAll()
 * @method Repayment[]    findBy(array<string, mixed> $criteria, array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class RepaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repayment::class);
    }
}