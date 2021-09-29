<?php

namespace App\Repository;

use App\Entity\GensetRealTimeData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GensetRealTimeData|null find($id, $lockMode = null, $lockVersion = null)
 * @method GensetRealTimeData|null findOneBy(array $criteria, array $orderBy = null)
 * @method GensetRealTimeData[]    findAll()
 * @method GensetRealTimeData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GensetRealTimeDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GensetRealTimeData::class);
    }

    // /**
    //  * @return GensetRealTimeData[] Returns an array of GensetRealTimeData objects
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
    public function findOneBySomeField($value): ?GensetRealTimeData
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
