<?php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use App\Entity\Facture;
use App\Entity\Revenu;
use App\Entity\FactureRevenu;
use App\Service\ApplicationManager;
use App\Repository\FactureRepository;
use App\Service\AuthorizationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RevenuService
{
    private $tokenStorage;
    private $authorization;
    private $entityManager;
    private $application;
    private $security;
    private $twig;
    private $factureRepo;

    public function __construct(
        AuthorizationManager $authorization, 
        TokenStorageInterface  $TokenStorageInterface, 
        EntityManagerInterface $entityManager,
        ApplicationManager  $applicationManager,
        Security $security,
        Environment $twig,
        FactureRepository $factureRepo

    )
    {
        $this->tokenStorage = $TokenStorageInterface;
        $this->authorization = $authorization;
        $this->entityManager = $entityManager;
        $this->application = $applicationManager->getApplicationActive();
        $this->security = $security;
        $this->twig = $twig;
        $this->factureRepo = $factureRepo;

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

    public function add($revenu = null, $folder = null, $request = null,$espece = null, $total = null, $mobileMoney = null, $factures = null)
    {   
        $user = $this->security->getUser();

        //créer nouveau Revenu
        $revenu->setEspece($espece);
        $revenu->setDateCreation(new \DateTime());
        $revenu->setMobileMoney($mobileMoney);
        $revenu->setTotal($total);
        $revenu->setApplication($this->application);

        foreach($factures as $facture) {
            $facture = $this->factureRepo->findOneBy(['id' => $facture['id']]);
            $facture->setRevenu($revenu);
            $facture->setIsRevenu(true);
            $revenu->addFacture($facture);
            $this->entityManager->persist($facture);
        }

        $this->entityManager->persist($revenu);

        //créer la facture Revenu
        $factureRevenu = new FactureRevenu();
        $date = new \DateTime();

        $numeroFacture = 1;
        //dd('ici');

        $tabNumeroFacture = $this->getLastValideFacture();

        if (count($tabNumeroFacture) > 0) {
            $numeroFacture = $tabNumeroFacture[0] + 1;
        }

        $factureRevenu->setNumero($numeroFacture);
        $factureRevenu->setApplication($this->application);

        $factureRevenu->setEtat('regle');
        $factureRevenu->setValid(true);
        $factureRevenu->setStatut('regle');
        $factureRevenu->setDateCreation($date);
        $factureRevenu->setDate($date);
        $factureRevenu->setType("Facture");

        //$depenses = $affaire->getProducts();
        $filename = 'Encaissement' . '-' . $factureRevenu->getNumero() . ".pdf";
       
        $factureRevenu->setFile($filename);
        $factureRevenu->setSolde($total);
        $factureRevenu->setPrixHt($total);    
        $factureRevenu->setReglement($total);    
        
        $this->entityManager->persist($factureRevenu);

        //dd($factureRevenu->getSolde());

        $this->update();
        
        // Initialize Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $pdf = new Dompdf($options);

        // Load HTML content
        $data = [];
        $data['factureRevenu'] = $factureRevenu;
        $data['application'] = $this->application;
        $data['user'] = $user;
        $data['factures'] = $factures;
        $data['Revenu'] = $revenu;
        
        $html = $this->twig->render('admin/Revenu/facturePdf.html.twig', $data);

        // Load HTML to Dompdf
        $pdf->loadHtml($html);

        // (Optional) Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Render PDF
        $pdf->render();

        // Get PDF content
        $pdfContent = $pdf->output();

        // Save PDF to file
        $fileName = $folder . $filename;
        file_put_contents($fileName, $pdfContent);

        return [$pdfContent, $factureRevenu, $revenu]; 
    }

    public function getLastValideFacture()
    {
        return $this->entityManager->getRepository(FactureRevenu::class)->getLastValideFacture();
    }
}