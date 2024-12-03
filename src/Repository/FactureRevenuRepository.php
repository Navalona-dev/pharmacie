<?php

namespace App\Repository;

use App\Entity\FactureRevenu;
use App\Service\ApplicationManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;

/**
 * @extends ServiceEntityRepository<FactureRevenu>
 */
class FactureRevenuRepository extends ServiceEntityRepository
{
    private $connection;
    private $application;

    public function __construct(ManagerRegistry $registry, ApplicationManager $applicationManager, Connection $connection)
    {
        parent::__construct($registry, FactureRevenu::class);
        $this->application = $applicationManager->getApplicationActive();
        $this->connection = $connection;
    }

    public function getLastValideFacture($type = "Facture")
    {
        $query = $this->createQueryBuilder('f')
            //->join('f.compte', 'c')
            
            ->andWhere('f.type = :type AND (f.isValid = 1 OR f.numero is not null)')
            ->setParameter('type', $type)
            ;
   
        $query = $query
        ->andWhere("f.application = :application")
        ->setParameter('application', $this->application)
        ;
   
        $results = $query->getQuery()->getResult();

        $tabNumero = [];

        if ($results) {
            foreach ($results as $result) {
                $tabNumero [] = intval($result->getNumero());
            }

            arsort($tabNumero);

            //Pour ordonner la clé
            $tabNumero = array_merge($tabNumero, []);
        }

        return $tabNumero;
    }
}
