<?php

namespace App\Repository;

use App\Entity\Masters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Masters|null find($id, $lockMode = null, $lockVersion = null)
 * @method Masters|null findOneBy(array $criteria, array $orderBy = null)
 * @method Masters[]    findAll()
 * @method Masters[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MastersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Masters::class);
    }

    // /**
    //  * @return Masters[] Returns an array of Masters objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Masters
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
