<?php

namespace App\Repository;

use App\Entity\AlarmReporting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AlarmReporting|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlarmReporting|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlarmReporting[]    findAll()
 * @method AlarmReporting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlarmReportingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlarmReporting::class);
    }

    // /**
    //  * @return AlarmReporting[] Returns an array of AlarmReporting objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AlarmReporting
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
