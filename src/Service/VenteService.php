<?php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use App\Entity\FactureComptabilite;
use App\Service\ApplicationManager;
use App\Repository\DepenseRepository;
use App\Service\AuthorizationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class VenteService
{
    private $tokenStorage;
    private $authorization;
    private $entityManager;
    private $application;
    private $depenseRepo;
    private $security;
    private $twig;

    public function __construct(
        AuthorizationManager $authorization, 
        TokenStorageInterface  $TokenStorageInterface, 
        EntityManagerInterface $entityManager,
        ApplicationManager  $applicationManager,
        DepenseRepository $depenseRepo,
        Security $security,
        Environment $twig

    )
    {
        $this->tokenStorage = $TokenStorageInterface;
        $this->authorization = $authorization;
        $this->entityManager = $entityManager;
        $this->application = $applicationManager->getApplicationActive();
        $this->depenseRepo = $depenseRepo;
        $this->security = $security;
        $this->twig = $twig;

    }

    public function add($methodePaiement = null, $Affaire = null)
    {
        $methodePaiement->setDateCreation(new \DateTime);
        $methodePaiement->setAffaire($Affaire);
        $methodePaiement->setApplication($this->application);
        $this->entityManager->persist($methodePaiement);
        $this->entityManager->flush();

        return $methodePaiement;
    }

    public function remove($entity)
    {
        $this->entityManager->remove($entity);
        return $entity;
    }

    public function update()
    {
        $this->entityManager->flush();
    }

    
}