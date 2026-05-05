<?php
namespace App\Repository;

use App\Entity\InsuredAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<InsuredAsset>
 *
 * @method InsuredAsset|null find($id, $lockMode = null, $lockVersion = null)
 * @method InsuredAsset|null findOneBy(array<string, mixed> $criteria, array<string, string> $orderBy = null)
 * @method InsuredAsset[]    findAll()
 * @method InsuredAsset[]    findBy(array<string, mixed> $criteria, array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class InsuredAssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InsuredAsset::class);
    }

    /**
     * Search, filter and sort assets for a given user.
     *
     * @return array<InsuredAsset>
     */
    public function search(
        UserInterface $user,
        ?string $query,
        ?string $type,
        string $orderBy = 'createdAt',
        string $dir = 'DESC'
    ): array {
        $criteria = ['user' => $user];
        if ($type) {
            $criteria['type'] = $type;
        }

        // Strip 'a.' prefix if present in orderBy for findBy
        $orderByField = str_replace('a.', '', $orderBy);

        return $this->findBy($criteria, [$orderByField => $dir]);
    }
}
