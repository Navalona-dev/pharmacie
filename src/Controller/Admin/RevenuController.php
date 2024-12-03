<?php

namespace App\Controller\Admin;

use App\Entity\Revenu;
use App\Form\RevenuType;
use App\Service\RevenuService;
use App\Service\ApplicationManager;
use App\Repository\DepenseRepository;
use App\Repository\FactureRepository;
use App\Repository\RevenuRepository;
use App\Repository\ComptabiliteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/Revenu', name: 'Revenus')]
class RevenuController extends AbstractController
{
    private $factureRepository;
    private $revenuService;
    private $depenseRepository;
    private $revenuRepo;
    private $application;
    private $comptabiliteRepo;

    public function __construct(
        FactureRepository $factureRepository,
        RevenuService $revenuService,
        DepenseRepository $depenseRepository,
        RevenuRepository $revenuRepo,
        ApplicationManager $applicationManager,
        ComptabiliteRepository $comptabiliteRepo

    )
    {
        $this->factureRepository = $factureRepository;
        $this->RevenuService = $revenuService;
        $this->depenseRepository = $depenseRepository;
        $this->RevenuRepo = $revenuRepo;
        $this->application = $applicationManager->getApplicationActive();
        $this->comptabiliteRepo = $comptabiliteRepo;
        
    }

    #[Route('/', name: '_liste')]
    public function index(): Response
    {
        $data = [];
        try {

            $revenus = $this->RevenuRepo->findByApplication();

            $data["html"] = $this->renderView('admin/Revenu/index.html.twig', [
                'listes' => $revenus,
            ]);

            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $data["html"] = "";
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route('/new', name: '_create')]
    public function create(Request $request)
    {
        $revenu = new Revenu();

        $form = $this->createForm(RevenuType::class, $revenu);
        $data = [];
        try {
            $dateFilterCommande = $request->getSession()->get('dateFilterCommande');
            //dd($dateFilterCommande);
            $factures = null;

            if($dateFilterCommande) {
                $factures = $this->factureRepository->selectFactureByDate('regle', $dateFilterCommande);
            } else {
                $factures = $this->factureRepository->selectFactureToday('regle');
            }

            
            $espece = 0;
            $mvola = 0;
            $airtel = 0;
            $orange = 0;

            foreach($factures as $facture) {
                $espece += $facture['espece'];
                $mvola += $facture['mVola'];
                $orange += $facture['orangeMoney'];
                $airtel += $facture['airtelMoney'];
            }

            $total = $espece + $mvola + $orange + $airtel;
            $mobileMoney = $mvola + $orange + $airtel;

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                
                if ($request->isXmlHttpRequest()) {

                    if (count($factures) > 0) {
                        $documentFolder = $this->getParameter('kernel.project_dir'). '/public/uploads/APP_'.$this->application->getId().'/factures/encaissement/';
            
                        // Vérifier si le dossier existe, sinon le créer avec les permissions appropriées
                        if (!is_dir($documentFolder)) {
                            mkdir($documentFolder, 0777, true);
                        }
                        
                        list($pdfContent, $facture, $returnRevenu) = $this->RevenuService->add($revenu, $documentFolder, $request, $espece, $total, $mobileMoney, $factures);

                        $filename = 'Encaissement' . '-' . $facture->getNumero() . ".pdf";
                        $pdfPath = '/uploads/APP_'.$this->application->getId().'/factures/encaissement/' . $filename;
                        
                        // Sauvegarder le fichier PDF
                        file_put_contents($this->getParameter('kernel.project_dir') . '/public' . $pdfPath, $pdfContent);
                        
                        return new JsonResponse([
                            'status' => 'success',
                            'pdfUrl' => $pdfPath,
                        ]);
                        
                    }
                }
            }

            $data['exception'] = "";
            $data["html"] = $this->renderView('admin/Revenu/new.html.twig', [
                'form' => $form->createView(),
                'espece' => $espece,
                'orange' => $orange,
                'mvola' => $mvola,
                'airtel' => $airtel,
                'factures' => $factures,
                'Revenu' => $revenu,
            ]);
           
            return new JsonResponse($data);

        }  catch (\Exception $Exception) {
            $data['exception'] = $Exception->getMessage();
            $data["html"] = "";
            return new JsonResponse($data);
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }

        return new JsonResponse($data);
    }

    #[Route('/detail/{id}', name: '_liste_one')]
    public function indexOne(Request $request, Revenu $revenu): Response
    {

        $data = [];
        try {

            $request->getSession()->set('RevenuId', $revenu->getId());

            $dateRevenu = $revenu->getDateRevenu();
            $dateRevenuFormat = $revenu->getDateRevenu()->format('Y-m-d');
            
            //$depensesToday = $this->depenseRepository->selectDepenseToday();
            $depenses = $this->depenseRepository->selectDepenseByDate($dateRevenu);

            $comptabilitesDate = $this->comptabiliteRepo->findAllDates();
            $tabDateCompta = [];

            foreach($comptabilitesDate as $comptabiliteDate) {
                $tabDateCompta[] = $comptabiliteDate['dateComptabilite']->format('Y-m-d');
            }

            $existeDate = false;

            if(in_array($dateRevenuFormat, $tabDateCompta)) {
                $existeDate = true;
            }

            $comptabilites = $revenu->getComptabilites();
            $comptabiliteFirst = $comptabilites[0];

            $totalDepense = 0;
            foreach($depenses as $depense) {
                $totalDepense += $depense['total'];
            }

            $request->getSession()->set('totalDepense', $totalDepense);
            $request->getSession()->set('totalRevenu', $revenu->getTotal());
            $request->getSession()->set('date', $revenu->getDateRevenu());

            $data["html"] = $this->renderView('admin/Revenu/detail.html.twig', [
                'depenses' => $depenses,
                'Revenu' => $revenu,
                'application' => $this->application,
                'existeDate' => $existeDate,
                'comptabiliteFirst' => $comptabiliteFirst
            ]);

            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $data["html"] = "";
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }
}
