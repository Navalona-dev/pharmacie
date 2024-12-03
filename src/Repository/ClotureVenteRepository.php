<?php

namespace App\Repository;

use App\Entity\ClotureVente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClotureVente>
 */
class ClotureVenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClotureVente::class);
    }

 

    public function ClotureVenteToday()
    {
        $today = new \DateTime('today'); // Récupère la date d'aujourd'hui sans l'heure
        $tomorrow = new \DateTime('tomorrow'); // Récupère la date de demain

        return $this->createQueryBuilder('c')
            ->andWhere('c.createdAt >= :today')
            ->andWhere('c.createdAt < :tomorrow')
            ->andWhere('c.isClose = :isClose') // Vérification que isClose est vrai
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('isClose', true)
            ->getQuery()
            ->getResult();
    }

    
}
