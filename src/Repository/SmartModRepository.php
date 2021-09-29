<?php

namespace App\Repository;

use App\Entity\SmartMod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SmartMod|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmartMod|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmartMod[]    findAll()
 * @method SmartMod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmartModRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmartMod::class);
    }

    // /**
    //  * @return SmartMod[] Returns an array of SmartMod objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SmartMod
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
