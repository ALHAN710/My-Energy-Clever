<?php

namespace App\Repository;

use App\Entity\LoadEnergyData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LoadEnergyData|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoadEnergyData|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoadEnergyData[]    findAll()
 * @method LoadEnergyData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoadEnergyDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoadEnergyData::class);
    }

    // /**
    //  * @return LoadEnergyData[] Returns an array of LoadEnergyData objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LoadEnergyData
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
