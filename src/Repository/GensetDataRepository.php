<?php

namespace App\Repository;

use App\Entity\GensetData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GensetData|null find($id, $lockMode = null, $lockVersion = null)
 * @method GensetData|null findOneBy(array $criteria, array $orderBy = null)
 * @method GensetData[]    findAll()
 * @method GensetData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GensetDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GensetData::class);
    }

    // /**
    //  * @return GensetData[] Returns an array of GensetData objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GensetData
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
