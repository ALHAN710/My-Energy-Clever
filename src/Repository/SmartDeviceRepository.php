<?php

namespace App\Repository;

use App\Entity\SmartDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SmartDevice|null find($id, $lockMode = null, $lockVersion = null)
 * @method SmartDevice|null findOneBy(array $criteria, array $orderBy = null)
 * @method SmartDevice[]    findAll()
 * @method SmartDevice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SmartDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmartDevice::class);
    }

    // /**
    //  * @return SmartDevice[] Returns an array of SmartDevice objects
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
    public function findOneBySomeField($value): ?SmartDevice
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
