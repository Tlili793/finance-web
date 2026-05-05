<?php
namespace App\Repository;

use App\Entity\InsuredContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InsuredContract>
 *
 * @method InsuredContract|null find($id, $lockMode = null, $lockVersion = null)
 * @method InsuredContract|null findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method InsuredContract[]    findAll()
 * @method InsuredContract[]    findBy(array<string, mixed> $criteria, array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class InsuredContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InsuredContract::class);
    }
}
