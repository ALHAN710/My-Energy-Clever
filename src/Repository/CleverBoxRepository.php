<?php

namespace App\Repository;

use App\Entity\CleverBox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CleverBox|null find($id, $lockMode = null, $lockVersion = null)
 * @method CleverBox|null findOneBy(array $criteria, array $orderBy = null)
 * @method CleverBox[]    findAll()
 * @method CleverBox[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CleverBoxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleverBox::class);
    }

    // /**
    //  * @return CleverBox[] Returns an array of CleverBox objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CleverBox
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
