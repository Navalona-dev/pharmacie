<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Compte;
use App\Entity\Affaire;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use App\Repository\CompteRepository;
use App\Service\AuthorizationManager;
use App\Exception\PropertyVideException;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\ActionInvalideException;
use Symfony\Component\Security\Core\Security;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
//use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AffaireService
{
    private $tokenStorage;
    private $authorization;
    private $entityManager;
    private $session;
    public  $isCurrentDossier = false;
    private $application;
    private $logger;
    private $security;
    private $compteRepo;

    public function __construct(
        AuthorizationManager $authorization, 
        TokenStorageInterface  $TokenStorageInterface, 
        EntityManagerInterface $entityManager, 
        ApplicationManager  $applicationManager,
        LoggerInterface $affaireLogger, 
        Security $security,
        CompteRepository $compteRepo
        )
    {
        $this->tokenStorage = $TokenStorageInterface;
        $this->authorization = $authorization;
        $this->entityManager = $entityManager;
        $this->application = $applicationManager->getApplicationActive();
        $this->logger = $affaireLogger;
        $this->security = $security;
        $this->compteRepo = $compteRepo;
    }

    public function add($instance, $statut, $compte = null, $application = null, $depot = null)
    {
        $affaire = Affaire::newAffaire($instance, $statut, $compte);

        //$affaire->setEtat($instance->getEtat());
        $affaire->setApplication($this->application);
        $affaire->setPrestation("Vente");
        $affaire->setNumero(null);
        if($statut == "commande" && $application != null) {
            $affaire->setApplicationRevendeur($application);
        }

        if($statut == "commande" && $depot == "on") {
            $affaire->setDepot(true);
        }
        
        /*foreach($affaire->getUtilisateur() as $utilisateur) {
            $utilisateur->addCompte($affaire);
            $this->entityManager->persist($utilisateur);
        }

        foreach($affaire->getCompteApplications() as $affaireApplication) {
            $this->entityManager->persist($affaireApplication);
        }*/

        $this->entityManager->persist($affaire);

          // Obtenir l'utilisateur connecté
          $user = $this->security->getUser();

          // Créer le message de log en fonction de l'action
          $logMessage = ($affaire->getStatut() == 'devis') ? 'Devis ajouté' : 'Commande ajoutée';
  
          // Créer le log
          $this->logger->info($logMessage, [
            'Commande' => $affaire->getNom(),
            'Nom du responsable' => $user ? $user->getNom() : 'Utilisateur non connecté',
            'Adresse e-mail' => $user ? $user->getEmail() : 'Pas d\'adresse e-mail',
            'ID Application' => $affaire->getApplication()->getId()
        ]);

        $this->update();
        unset($instance);
        return $affaire;
    }

    public function update()
    {
        $this->entityManager->flush();
    }

    public function persist($affaire)
    {
        $this->entityManager->persist($affaire);
    }

    public function remove($affaire)
    {
        $factures = $affaire->getFactures();
        foreach($factures as $facture) {
            $factureDetails = $facture->getFactureDetails();
            foreach($factureDetails as $factureDetail) {
                $this->entityManager->remove($factureDetail);
            }

            $factureEcheances = $facture->getFactureEcheances();
            foreach($factureEcheances as $factureEcheance) {
                $this->entityManager->remove($factureEcheance);
            }

            $this->entityManager->remove($facture);

            
        }
        $this->entityManager->remove($affaire);
    }

    public function find($id)
    {
        return $this->entityManager->getRepository(Affaire::class)->find($id);
    }

    public function getAllAffaire($compte = null, $start = 1, $limit = 0, $statut = null)
    {
        return $this->entityManager->getRepository(Affaire::class)->searchAffaire($compte, null,null, $limit, $start, $statut);
    }

    public function searchCompteRawSql($genre, $nom, $dateDu, $dateAu, $etat, $start, $limit, $order, $isCount)
    {
        return $this->entityManager->getRepository(Affaire::class)->searchCompteRawSql($genre, $nom, $dateDu,$dateAu, $etat, $limit, $start, $order, $isCount);
    }

    public function getNombreTotalCompte()
    {
        return $this->entityManager->getRepository(Affaire::class)->countAll();
    }

    public function getAffaires($statut = null)
    {
        return $this->entityManager->getRepository(Affaire::class)->getAllAffaires($statut);
    }

    public function ajout($affaire, $nom)
    {
        $comptes = $this->compteRepo->findBy(['genre' => 1]);
        $compte = null;
        if(count($comptes) > 0) {
            $compte = $comptes[0];
        }else {
            $compte = new Compte();
            $compte->setNom('Standard');
            $compte->setGenre(1);
            $compte->setApplication($this->application);
            $this->entityManager->persist($compte);
        }


        $affaire->setNom($nom);
        $affaire->setApplication($this->application);
        $affaire->setPrestation("Vente");
        $affaire->setStatut("commande");
        $affaire->setCompte($compte);
        $affaire->setNumero(null);

        $this->entityManager->persist($affaire);

          // Obtenir l'utilisateur connecté
          $user = $this->security->getUser();

          // Créer le message de log en fonction de l'action
          $logMessage = ($affaire->getStatut() == 'devis') ? 'Devis ajouté' : 'Commande ajoutée';
  
          // Créer le log
          $this->logger->info($logMessage, [
            'Commande' => $affaire->getNom(),
            'Nom du responsable' => $user ? $user->getNom() : 'Utilisateur non connecté',
            'Adresse e-mail' => $user ? $user->getEmail() : 'Pas d\'adresse e-mail',
            'ID Application' => $affaire->getApplication()->getId()
        ]);

        $this->update();
        unset($instance);
        return $affaire;
    }

    public function ajoutProductInAffaire($product = null, $produitCategory = null, $qtt = null, $typeVente = null, $affaire = null)
    {

        $product->setNom($produitCategory->getNom());
        $product->setReference($produitCategory->getReference());
        $product->setQtt($qtt);
        $product->setTypeVente($typeVente);
      
        $product->setUniteVenteGros($produitCategory->getPresentationGros());
        $product->setUniteVenteDetail($produitCategory->getUniteVenteDetail());
        $product->setPrixAchat($produitCategory->getPrixAchat());
        $product->setProduitCategorie($produitCategory);
        $product->getDateCreation(new \DateTime());
        $product->setApplication($this->application);
        $product->setPrixVenteGros($produitCategory->getPrixVenteGros());
        $product->setPrixVenteDetail($produitCategory->getPrixVenteDetail());

        $product->addAffaire($affaire);
        $affaire->addProduct($product);

        $volumeGros = $produitCategory->getVolumeGros();

        if($typeVente == "gros") {
            if($produitCategory->getQttReserverGros()) {
                $produitCategory->setQttReserverGros($product->getQtt() + $produitCategory->getQttReserverGros());
            } else {
                $produitCategory->setQttReserverGros($product->getQtt());
            }

        } elseif($typeVente == "detail") {
            if($produitCategory->getQttReserverDetail()) {
                $produitCategory->setQttReserverDetail($product->getQtt() + $produitCategory->getQttReserverDetail());
            } else {
                $produitCategory->setQttReserverDetail($product->getQtt());
            }
        }

        $this->entityManager->persist($product);

        $this->update();
        unset($instance);
        return $product;
    }
}