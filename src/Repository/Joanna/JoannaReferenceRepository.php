<?php

namespace App\Repository\Joanna;

use App\Entity\Joanna\JoannaReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JoannaReference>
 *
 * @method JoannaReference|null find($id, $lockMode = null, $lockVersion = null)
 * @method JoannaReference|null findOneBy(array $criteria, array $orderBy = null)
 * @method JoannaReference[]    findAll()
 * @method JoannaReference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JoannaReferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JoannaReference::class);
    }
}
