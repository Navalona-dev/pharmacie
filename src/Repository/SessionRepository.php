<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function findByDateDebut($date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('DATE(s.dateDebut) = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findByApplication($id)
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.application', 'a')
            ->where('a.id = :applicationId')
            ->setParameter('applicationId', $id)
            ->getQuery()
            ->getResult();
    }
}
