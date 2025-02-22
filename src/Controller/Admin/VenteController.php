<?php

namespace App\Controller\Admin;

use App\Entity\Compte;
use App\Entity\Revenu;
use App\Entity\Affaire;
use App\Entity\Product;
use App\Form\RevenuType;
use App\Form\AffaireType;
use App\Form\ProductType;
use App\Entity\ClotureVente;
use App\Service\AccesService;
use App\Service\VenteService;
use App\Form\ClotureVenteType;
use App\Entity\MethodePaiement;
use App\Service\AffaireService;
use App\Service\ProductService;
use App\Form\MethodePaiementType;
use App\Service\CategorieService;
use App\Service\ApplicationManager;
use App\Service\ApplicationService;
use App\Form\AddFactureEcheanceType;
use App\Repository\CompteRepository;
use App\Repository\RevenuRepository;
use App\Form\ChangeCompteAffaireType;
use App\Repository\AffaireRepository;
use App\Repository\DepenseRepository;
use App\Repository\ProductRepository;
use App\Repository\SessionRepository;
use App\Service\FactureEcheanceService;
use App\Exception\PropertyVideException;
use App\Service\ProduitCategorieService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApplicationRepository;
use App\Repository\ClotureVenteRepository;
use App\Repository\MethodePaiementRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProduitCategorieRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/vente', name: 'ventes')]
class VenteController extends AbstractController
{
    private $applicationService;
    private $accesService;
    private $application;
    private $produitCategorieRepo;
    private $categorieService;
    private $produitCategorieService;
    private $affaireService;
    private $applicationRepo;
    private $affaireRepo;
    private $productRepo;
    private $productService;
    private $entityManager;
    private $compteRepo;
    private $factureEcheanceService;
    private $venteService;
    private $methodePaiementRepo;
    private $clotureVenteRepo;
    private $depenseRepo;
    private $revenuRepo;
    private $sessionRepo;
    
    public function __construct(
        ApplicationService $applicationService, 
        ApplicationManager $applicationManager, 
        AccesService $accesService,
        ProduitCategorieRepository $produitCategorieRepo,
        CategorieService $categorieService,
        ProduitCategorieService $produitCategorieService,
        AffaireService $affaireService,
        ApplicationRepository $applicationRepo,
        AffaireRepository $affaireRepo,
        ProductRepository $productRepo,
        ProductService $productService,
        EntityManagerInterface $entityManager,
        CompteRepository $compteRepo,
        FactureEcheanceService $factureEcheanceService,
        VenteService $venteService,
        MethodePaiementRepository $methodePaiementRepo,
        ClotureVenteRepository $clotureVenteRepo,
        DepenseRepository $depenseRepo,
        RevenuRepository $revenuRepo,
        SessionRepository $sessionRepo

        )
    {
        $this->applicationService = $applicationService;
        $this->accesService = $accesService;
        $this->application = $applicationManager->getApplicationActive();
        $this->produitCategorieRepo = $produitCategorieRepo;
        $this->categorieService = $categorieService;
        $this->produitCategorieService = $produitCategorieService;
        $this->applicationRepo = $applicationRepo;
        $this->affaireService = $affaireService;
        $this->affaireRepo = $affaireRepo;
        $this->productRepo = $productRepo;
        $this->productService = $productService;
        $this->entityManager = $entityManager;
        $this->compteRepo = $compteRepo;
        $this->factureEcheanceService = $factureEcheanceService;
        $this->venteService = $venteService;
        $this->methodePaiementRepo = $methodePaiementRepo;
        $this->clotureVenteRepo = $clotureVenteRepo;
        $this->depenseRepo = $depenseRepo;
        $this->revenuRepo = $revenuRepo;
        $this->sessionRepo = $sessionRepo;
    }
    
    #[Route('/', name: '_liste')]
    public function index(Request $request, SessionRepository $sessionRepo): Response
    {
        $data = [];
        try {

            $sessionId = $request->getSession()->get('currentSession');
            $session = $sessionRepo->findOneBy((['id' => $sessionId]));

            $affaires = $this->affaireRepo->findBy(['session' => $session ]);
            //dd($affaires);
            
            $tabIdProduitCategorieInAffaires = [];
            $categories = $this->categorieService->getAllCategories();
            $produitCategories = $this->produitCategorieService->getAllProduitCategoriesByStockRestant($tabIdProduitCategorieInAffaires);

            $affaire = new Affaire();

            $applications = $this->applicationRepo->findByApplication($this->application->getId());
            
            $form = $this->createForm(AffaireType::class, $affaire);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($request->isXmlHttpRequest()) {
                    // encode the plain password
                    $this->affaireService->ajout($affaire, $affaire->getNom());

                    $this->affaireService->update();
                   
                }
            }

            $data["html"] = $this->renderView('admin/vente/index.html.twig', [
                //'listes' => $categories,
                'produitCategories' => $produitCategories,
                'categories' => $categories,
                'form' => $form->createView(),
                'affaires' => $affaires,
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route('/new/commande', name: '_new_commande', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $data = [];
        try {
            $affaire = new Affaire();
            $form = $this->createForm(AffaireType::class, $affaire);
            $form->handleRequest($request);
            $sessionId = $request->getSession()->get('currentSession');

            if ($form->isSubmitted() && $form->isValid()) {
                // Enregistre l'affaire dans la base de données
                $formData = $form->getData();

                $nom = $formData->getNom();

                $this->affaireService->ajout($affaire, $nom, $sessionId);

                return new JsonResponse(['success' => true, 'message' => 'Vente enregistrée.']);
            }

            return new JsonResponse(['success' => false, 'message' => 'Formulaire invalide.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    #[Route('/affaire/update', name: '_affaire_update')]
    public function getUpdatedAffaires()
    {
        $affaires = $this->affaireRepo->getAffaireToday();

        $response = [];
        foreach ($affaires as $affaire) {
            $response[] = [
                'id' => $affaire->getId(),
                'nom' => $affaire->getNom(),
                'isValid' => $affaire->getIsValid(),
            ];
        }

        return new JsonResponse($response);
    }

    #[Route('/avoir', name: '_avoir')]
    public function avoir(): Response
    {
        $data = [];
        try {
            
           
            $data["html"] = $this->renderView('admin/vente/annuler_vente.html.twig', [
                //'listes' => $categories,
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route('/depense', name: '_depense')]
    public function depense(Request $request): Response
    {
        $data = [];
        try {

            $sessionId = $request->getSession()->get('sessionIdClose');
            
            $depenses = $this->depenseRepo->selectDepenseBySession($sessionId);
           
            $data["html"] = $this->renderView('admin/vente/depense.html.twig', [
                'listes' => $depenses,
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route('/liste/vente', name: '_liste_vente')]
    public function listeVente(
        Request $request,
        SessionRepository $sessionRepo
        ): Response
    {
        $data = [];
        try {

            $sessionId = null;
            
            $sessionIdOpen = $request->getSession()->get('currentSession');
            $sessionIdClose = $request->getSession()->get('sessionIdClose');

            if($sessionIdOpen) {
                $sessionId = $sessionIdOpen;
            } elseif($sessionIdClose) {
                $sessionId = $sessionIdClose;
            }
    
            $affaires = $this->affaireRepo->getAffaireTodayPayerBySession($sessionId);
    
            $montantHt = [];
            $totalMontantHt = 0; // Variable pour le total
            $totalRemise = 0;
            $products = [];
            $methodePaiements = [];
            $totalRemises = [];
            $totalMobileMoney = 0;
            $totalEspece = 0;
            $tabMobileMoney = [];
            $tabEspece = [];
    
            foreach ($affaires as $affaire) {
                // Récupérer les méthodes de paiement
                $methodes = $affaire->getMethodePaiements();
                if (count($methodes) > 0) {
                    $methodePaiement = $methodes[0]; // Récupère la première méthode de paiement
                    $methodePaiements[$affaire->getId()] = $methodePaiement;
    
                    // Initialiser le montant HT si la clé n'existe pas encore
                    if (!isset($montantHt[$affaire->getId()])) {
                        $montantHt[$affaire->getId()] = 0;
                    }
    
                    // Calculer le montant HT pour cette affaire
                    $montantHt[$affaire->getId()] += $methodePaiement->getMVola() 
                        + $methodePaiement->getEspece() 
                        + $methodePaiement->getAirtelMoney() 
                        + $methodePaiement->getOrangeMoney();
    
                    // Ajouter ce montant au total
                    $totalMontantHt += $montantHt[$affaire->getId()];
                    $tabMobileMoney[$affaire->getId()] = $methodePaiement->getMvola() + $methodePaiement->getAirtelMoney() + $methodePaiement->getOrangeMoney();
                    $totalMobileMoney += $tabMobileMoney[$affaire->getId()];
                    $tabEspece[$affaire->getId()] = $methodePaiement->getEspece();
                    $totalEspece += $tabEspece[$affaire->getId()];
                }
    
                // Calculer la remise totale pour cette affaire
                $totalRemise = 0; // Réinitialiser le total de remise pour chaque affaire
                $products = $affaire->getProducts(); // Récupérer les produits de l'affaire
    
                foreach ($products as $product) {
                    $totalRemise += $product->getRemise(); // Ajouter la remise du produit
                }
    
                $totalRemises[$affaire->getId()] = $totalRemise; // Associer la remise totale à l'affaire
            }

            $sessionVenteId = $request->getSession()->get('sessionIdClose');
            $sessionVente = $sessionRepo->findOneBy(['id' => $sessionVenteId]);

            //gerer revenu
            $revenu = new Revenu();
            $form = $this->createForm(RevenuType::class, $revenu, []);
            $form->handleRequest($request);

            $request->getSession()->set('espece', $totalEspece);
            $request->getSession()->set('mobileMoney', $totalMobileMoney);
            $request->getSession()->set('total', $totalMontantHt);

            $data["html"] = $this->renderView('admin/vente/liste_vente.html.twig', [
                'listes' => $affaires,
                'methodePaiements' => $methodePaiements,
                'totalRemises' => $totalRemises,
                'montantHt' => $montantHt,
                'totalMontantHt' => $totalMontantHt,
                'totalMobileMoney' => $totalMobileMoney,
                'totalEsepce' => $totalEspece,
                'form' => $form->createView()
            ]);
    
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route('/comptabilite', name: '_comptabilite')]
    public function comptabilite(Request $request): Response
    {
        $data = [];
        try {

            $sessionId = $request->getSession()->get('sessionIdClose');

            $session = $this->sessionRepo->findOneBy(['id' => $sessionId]);

            $revenus = $this->revenuRepo->findBy(['session' => $session]);
            $depenses = $this->depenseRepo->findBy(['session' => $session]);
            
            $data["html"] = $this->renderView('admin/vente/comptabilite.html.twig', [
                'revenus' => $revenus,
                'depenses' => $depenses
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }
    
    #[Route('/new/revenu', name: '_new_revenu', methods: ['POST'])]
    public function newRevenu(Request $request): JsonResponse
    {
        $data = [];
        try {

            $espece = $request->getSession()->get('espece');
            $mobileMoney = $request->getSession()->get('mobileMoney');
            $montantHt = $request->getSession()->get('total');
            $sessionId = $request->getSession()->get('sessionIdClose');

            $revenu = new Revenu();
            $form = $this->createForm(RevenuType::class, $revenu);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Enregistre l'affaire dans la base de données
                $formData = $form->getData();

                $designation = $formData->getDesignation();
                $dateRevenu = $formData->getDateRevenu();

                $this->venteService->addRevenu($revenu, $designation, $dateRevenu, $espece, $mobileMoney, $montantHt, $sessionId);

                return new JsonResponse(['success' => true, 'message' => 'Revenu enregistrée.']);
            }

            return new JsonResponse(['success' => false, 'message' => 'Formulaire invalide.']);
        } catch (\Exception $exception) {
            return new JsonResponse(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @param ProductRepository $productRepository
     * @param AffaireRepository $affaireRepository
     * @Route("/addRemise/ajax", name="_add_remise_product", methods={"POST"})
     * @return Response
     */
    public function addRemise(
        Request               $request,
        ProductRepository     $productRepository,
        AffaireRepository     $affaireRepository
    )
    {
        $idProduit = $request->get('idProduit');

        $idAffaire = $request->get('idAffaire');
        
        $type = $request->get('type');

        $isFrais = $request->get('isFrais');

        $option = $request->get('option');

        $uri = $request->get('uri');

        $produit = [];

        $affaire = [];

        $montantRemise = "";

        $montantPourcent = "";

        switch ($type) {
            case "produit":
                $produit = $productRepository->find($idProduit);

                $montantRemise = $produit->getRemise();

                $montantTotal = $produit->getPrixVenteDetail() * $produit->getQtt();
                if ($produit->getTypeVente() == "gros") {
                    $montantTotal = $produit->getPrixVenteGros() * $produit->getQtt();
                }
                
                $montantPourcent = $produit->getRemisePourcent();

                break;

            case "affaire":
                $affaire = $affaireRepository->find($idAffaire);

                $montantRemise = $affaire->getRemise();

                $produits = $affaire->getProducts();

                
                $applicationId = $this->application->getId();

                $produits = $produits->filter(function ($item) use ($applicationId) {
                    return $item->getApplication()->getId() == $applicationId;
                });

                $montantTotal = 0;

                foreach ($produits as $unProduit) {
                    if ($unProduit->getTypeReduction()) {
                        if ($unProduit->getTypeReduction() == "remise" && null == $isFrais) {
                            $montantTotal = $montantTotal + $unProduit->getPuHt() * $unProduit->getQtt() - $unProduit->getRemise();
                        }
                    } else
                        $montantTotal = $montantTotal + $unProduit->getPuHt() * $unProduit->getQtt() - $unProduit->getRemise();
                }

                $montantPourcent = $affaire->getRemisePourcent();

                break;
        }


        return $this->render('admin/vente/modalRemise.html.twig', [
            'produit' => $produit,
            'affaire' => $affaire,
            'type' => $type,
            'montantTotal' => $montantTotal,
            'uri' => $uri,
            'montantRemise' => $montantRemise,
            'montantPourcent' => $montantPourcent,
            'tvaVentes' => [],
            'option' => $option,
            'isFrais' => $isFrais,
            'idAffaire' => $idAffaire
        ]);
    }

    /**
     * @param Request $request
     * @param ProduitRepository $produitRepository
     * @param AffaireRepository $affaireRepository
     * @Route("/deleteAddRemise/ajax", name="_delete_vente_add_remise_product_ajax", methods={"POST"})
     * @return Response
     */
    public function deleteAddRemiseVente(Request $request, ProductRepository   $productRepository, AffaireRepository $affaireRepository, ProductService   $productService)
    {
        $idProduit = $request->get('idProduit');

        $idAffaire = $request->get('idAffaire');

        $type = $request->get('type');

        $montantRemiseFinale = $request->get('montantRemiseFinale');

        $uri = $request->get('uri');

        $produit = [];

        $affaire = [];
        switch ($type) {
            case "produit":
                $produit = $productRepository->find($idProduit);

                $affaires = $produit->getAffaires();

                $affaire = $affaires[0];

                $remiseAffaire = $affaire->getRemiseProduit();

                if (null != $remiseAffaire) {
                } else {
                    $remiseAffaire = 0;
                }

                $affaire->setRemiseProduit($remiseAffaire - $produit->getRemise());

                $produit->setRemise(null);

                $produit->setRemisePourcent(null);

                $productService->persist($produit);

                $productService->persist($affaire);

                break;

            case "affaire":
                $affaire = $affaireRepository->find($idAffaire);

                $affaire->setRemise(null);

                $affaire->setRemisePourcent(null);

                $productService->persist($affaire);

                break;
        }

        $productService->update();
        
       $products = $this->productService->findProduitAffaire($affaire);

       $montantHt = 0;
       $puHt = 0;

       foreach($products as $product) {
        if($product->getTypeVente() == "gros") {
            $puHt = $product->getPrixVenteGros();
        }elseif($product->getTypeVente() == "detail") {
            $puHt = $product->getPrixVenteDetail();
        }

        $total = $puHt * $product->getQtt();
        if($product->getRemise()) {
            $total = $puHt * $product->getQtt() - $product->getRemise();
        }

        $montantHt = $montantHt + $total;
       }

       $request->getSession()->set('idAffaire', $affaire->getId());

       return $this->render("admin/vente/reload_caisse.html.twig", [
           'affaire' => $affaire,
           'montantHt' => $montantHt,
           'products' => $products

       ]);
    }

    /**
     * @param Request $request
     * @param ProduitRepository $produitRepository
     * @param AffaireRepository $affaireRepository
     * @Route("/valideAddRemise/ajax", name="_valide_add_remise_product_ajax", methods={"POST"})
     * @return Response
     */
    public function valideAddRemise(
        Request                       $request,
        ProductRepository             $productRepository,
        AffaireRepository             $affaireRepository,
        ProductService                $productService,
        ApplicationRepository         $applicationRepository,
        ApplicationManager            $applicationManager
    )
    {
        $idProduit = $request->get('idProduit');

        $idAffaire = $request->get('idAffaire');

        $applicationCurrentInSession = $this->application;

        $type = $request->get('type');

        $montantRemise = $request->get('montantRemise');

        $montantRemiseFinale = $request->get('montantRemiseFinale');
     
        $typeRemise = $request->get('typeremise');

        $uri = $request->get('uri');

        $typeReduction = $request->get('typeReduction');

        $produit = [];

        $affaire = [];
        switch ($type) {
            case "produit":
                $produit = $productRepository->find($idProduit);
                
                $produit->setRemise($montantRemiseFinale);

                $affaires = $produit->getAffaires();

                $affaire = $affaires[0];

                $remiseAffaire = $affaire->getRemiseProduit();

                if (null != $remiseAffaire) {
                } else {
                    $remiseAffaire = 0;
                }

                $remiseProduit = $montantRemiseFinale + $remiseAffaire;

                $affaire->setRemiseProduit($montantRemiseFinale);

                if ($typeRemise == "1") {
                    $produit->setRemisePourcent($request->get('montantRemise'));
                } else {
                    $produit->setRemisePourcent(null);
                }

                if (is_null($produit->getApplication())) {
                    $produit->setApplication($this->application);
                }

                $productService->persist($produit);

                $productService->persist($affaire);

                $montantRemiseFinale = 0;

                break;

            case "affaire":
                $affaire = $affaireRepository->find($idAffaire);

                $montantRemise = $affaire->getRemise();

                $tva = $request->get('tva');

                $titreRemise = (null != $request->get('titreRemise')) ? $request->get('titreRemise') : "Remise globale";

                $idProduit = $request->get('id');

                if (null != $idProduit) {
                    $produit = $productRepository->find($idProduit);
                } else {
                    $produit = new Product();
                    $produit->addAffaire($affaire);
                }

                if ($montantRemiseFinale < 0) {
                    $montantRemiseFinale = $montantRemiseFinale * -1;
                }

                //$affaire->setRemise($montantRemise + $montantRemiseFinale);
                if ($typeReduction == "frais") {
                    $montantRemiseFinale = $montantRemiseFinale * -1;
                }

                $produit->setNom($titreRemise)
                    ->setQtt(1)
                    ->setPuHt($montantRemiseFinale * -1)
                    ->setTva($tva);

                if (is_null($produit->getApplication())) {
                    $produit->setApplication($this->application);
                }

                if ($typeRemise == "1") {
                    $produit->setRemisePourcent($request->get('montantRemise'));
                } else {
                    $produit->setRemisePourcent(null);
                }

                $typeReduction = (null != $request->get('typeReduction')) ? $request->get('typeReduction') : "remise";

                $produit->setTypeReduction($typeReduction);


                $productService->persist($produit);
                $productService->persist($affaire);


                break;
        }

        $productService->update();

        $products = $this->productService->findProduitAffaire($affaire);

        $montantHt = 0;
        $puHt = 0;

        foreach($products as $product) {
         if($product->getTypeVente() == "gros") {
             $puHt = $product->getPrixVenteGros();
         }elseif($product->getTypeVente() == "detail") {
             $puHt = $product->getPrixVenteDetail();
         }

         $total = $puHt * $product->getQtt();
         if($product->getRemise()) {
             $total = $puHt * $product->getQtt() - $product->getRemise();
         }

         $montantHt = $montantHt + $total;
        }
       
        $request->getSession()->set('idAffaire', $affaire->getId());

        return $this->render("admin/vente/reload_caisse.html.twig", [
            'affaire' => $affaire,
            'montantHt' => $montantHt,
            'products' => $products

        ]);
    }

    #[Route('/caisse/{id}', name: '_caisse')]
    public function caisse(int $id, Request $request): Response
    {
        $data = [];
        try {

           $affaire = $this->affaireRepo->findOneBy(['id' => $id]);

           if (!$affaire) {
                throw $this->createNotFoundException("Affaire avec ID $id introuvable.");
            }

           $products = $affaire->getProducts();
           $montantHt = 0;
           $puHt = 0;

           foreach($products as $product) {
            if($product->getTypeVente() == "gros") {
                $puHt = $product->getPrixVenteGros();
            }elseif($product->getTypeVente() == "detail") {
                $puHt = $product->getPrixVenteDetail();
            }

            $total = $puHt * $product->getQtt();
            if($product->getRemise()) {
                $total = $puHt * $product->getQtt() - $product->getRemise();
            }

            $montantHt = $montantHt + $total;
           }

           $request->getSession()->set('idAffaire', $affaire->getId());

            $data["html"] = $this->renderView('admin/vente/caisse.html.twig', [
                'affaire' => $affaire,
                'montantHt' => $montantHt,
                'products' => $products
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route("/affaire/{id}/produits", name:"get_produits_par_affaire", methods:["GET"])]
    public function getProduitsParAffaire(int $id, Request $request): JsonResponse
    {
        $affaire = $this->affaireRepo->findOneBy(['id' => $id]);

        $request->getSession()->set('idAffaire', $affaire->getId());

        if (!$affaire) {
            return new JsonResponse(['error' => 'Affaire not found'], JsonResponse::HTTP_NOT_FOUND);
        }
    
        $produits = $affaire->getProducts();

        $produitsData = [];
        $montantHt = 0;

        foreach ($produits as $produit) {
            $puHt = 0;

            if($produit->getTypeVente() == "gros") {
                $puHt = $produit->getPrixVenteGros();
            } elseif($produit->getTypeVente() == "detail") {
                $puHt = $produit->getPrixVenteDetail();
            }

            $total = $produit->getQtt() * $puHt;

            $montantHt = $montantHt + $total;

            $unite = null;

            if($produit->getTypeVente() == 'gros') {
                $unite = $produit->getUniteVenteGros();
            } elseif($produit->getTypeVente() == 'detail') {
                $unite = $produit->getUniteVenteDetail();
            }

            $produitsData[] = [
                'id' => $produit->getId(),
                'reference' => $produit->getReference(),
                'nom' => $produit->getNom(),
                'puHt' => $puHt, 
                'qtt' => $produit->getQtt(), 
                'total' => $total,
                'unite' => $unite,
            ];
        }

        return new JsonResponse([
            'produits' => $produitsData,
            'montantHt' => $montantHt,
        ]);
    }

    #[Route('/produit/category/{id}', name: '_produit_category')]
    public function produitCategory(int $id, Request $request): Response
    {
        $data = [];
        try {

            $idAffaire = $request->getSession()->get('idAffaire');

            $affaire = $this->affaireRepo->findOneBy(['id' => $idAffaire]);

           $produitCategory = $this->produitCategorieRepo->findOneBy(['id' => $id]);

           $product = new Product();
            $form = $this->createForm(ProductType::class, $product);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Enregistre l'affaire dans la base de données
                $formData = $form->getData();

                $qtt = $formData->getQtt();
                $typeVente = $formData->getTypeVente();

                $this->affaireService->ajoutProductInAffaire($product, $produitCategory, $qtt, $typeVente, $affaire);

                return new JsonResponse(['success' => true, 'message' => 'Vente enregistrée.']);
            }

            $data["html"] = $this->renderView('admin/vente/new_product.html.twig', [
                'produitCategory' => $produitCategory,
                'form' => $form,
                'affaire' => $affaire
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
    }

    #[Route('/edit_produit', name: '_edit_tab_financiere')]
    public function editTabFinanciere(
        Request               $request,
        AffaireRepository     $affaireRepository
    )
    {

        $idProduit = $request->get('idProduit');

        $idAffaire = $request->get('idAffaire');

        $affaire = $this->affaireService->find($idAffaire);

        $product = $this->productService->getProductById($idProduit);
        
        $template = "tr_edit_financiere.html.twig";
        
        return $this->render('admin/affaires/' . $template, [
            'affaire' => $affaire,
            'product' => $product,
            'qttRestant' => ($product->getProduitCategorie()->getStockRestant() != null ? $product->getProduitCategorie()->getStockRestant() : 0),
            
        ]);
    }

    #[Route('/save/edit_produit', name: '_save_edit_product')]
    public function editProductAffaire(
        Request               $request
    )
    {

        $affaires = $this->affaireRepo->getAffaireToday();
            
        $tabIdProduitCategorieInAffaires = [];
        $categories = $this->categorieService->getAllCategories();
        $produitCategories = $this->produitCategorieService->getAllProduitCategoriesByStockRestant($tabIdProduitCategorieInAffaires);

        $affaire = new Affaire(); // ou récupérez une instance existante si nécessaire
        $form = $this->createForm(AffaireType::class, $affaire);

        $idProduit = $request->get('idProduit');

        $idAffaire = $request->get('idAffaire');

        $qtt = $request->get('qtt');

        $affaire = $this->affaireService->find($idAffaire);

        $product = $this->productService->getProductById($idProduit);
        
        if ($product) {
            $this->productService->setQttReserver($product,$qtt);
            $this->productService->persist($product);
            $this->productService->persist($product);
            $this->productService->update();
        }
        return new JsonResponse();

    }

    #[Route('/delete-produit', name: '_delete_product')]
    public function deleteProductAffaire(
        Request               $request
    )
    {

        $idProduit = $request->get('idProduit');

        $idAffaire = $request->get('idAffaire');

        $affaire = $this->affaireService->find($idAffaire);

        $product = $this->productService->getProductById($idProduit);
        if ($product) {
            $qtt = $product->getQtt();
            $this->productService->remove($product, $affaire);
            $this->productService->setQttReserver($product,$qtt, true);
            $this->productService->update();
        }

        return new JsonResponse();

    }

    #[Route('/add/compte', name: '_add_compte')]
    public function addCompte(Request $request)
    {
        $data = []; 
    
        try {
            // Récupérez le nom envoyé via la requête AJAX
            $nom = $request->request->get('nom'); 
    
            // Créez une nouvelle instance de compte
            $compte = new Compte();
            
            // Si vous avez un setter pour le nom dans compte$compte, utilisez-le
            $compte->setNom($nom); 
            $nom = $compte->getNom(); // Récupérer le nom
            $nomSansEspaces = preg_replace('/\s+/', '', $nom); // Supprimer tous les espaces
            $indiceFacture = strtoupper(substr($nomSansEspaces, 0, 2)); // Prendre les 2 premières lettres en majuscules

            // Générer 3 chiffres aléatoires
            $chiffres = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

            // Construire l'indice facture
            $indiceFactureComplet = $indiceFacture . $chiffres;

            // Affecter l'indice facture
            $compte->setIndiceFacture($indiceFactureComplet);


            $compte->setApplication($this->application);
            $compte->setDateCreation(new \DateTime());
            $compte->setGenre(1);
    
            $this->entityManager->persist($compte);
            $this->entityManager->flush();

            $data['id'] = $compte->getId();
    
            return new JsonResponse($data);
        } catch (PropertyVideException $PropertyVideException) {
            $data['exception'] = $PropertyVideException->getMessage();
            $data["html"] = "";
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data['exception'] = $Exception->getMessage();
            return new JsonResponse($data);
        }
        
        return new JsonResponse($data);
    }


    #[Route('/echeance/{affaire}', name: '_echeance_create')]
    public function echeance(Affaire $affaire, Request $request): Response
    {
        $request->getSession()->set('idAffaire', $affaire->getId());
        $applicationRevendeur = $affaire->getApplicationRevendeur();

        $data = [];

        try {
            $produits = $this->productService->findProduitAffaire($affaire);
            if ($produits == false) {
                $produits = [];
            }

            $form = $this->createForm(AddFactureEcheanceType::class, null);
            $form->handleRequest($request);

            $form_affaire = $this->createForm(ChangeCompteAffaireType::class, null, [
                'application' => $this->application
            ]);
            $form_affaire->handleRequest($request);

            $facture = null;
            $montant = 0;
            $totalPayer = 0;

            if($form->isSubmitted() && $form->isValid()) {
                $compteId = (int) $request->get('compte-id');

                $compte = null;

                if($compteId) {
                    $compte = $this->compteRepo->findOneBy(['id' => $compteId]);
                }

                $montantHt = $request->request->get('montantHt');

                if ($request->isXmlHttpRequest()) {
                    $documentFolder = $this->getParameter('kernel.project_dir'). '/public/uploads/APP_'.$this->application->getId().'/factures/valide/';
                     // Chemin du dossier des échéances
                    $echeanceFolder = $this->getParameter('kernel.project_dir') . '/public/uploads/APP_'.$this->application->getId().'/factures/echeance/';
                    
                    // Vérifier si le dossier des échéances existe, sinon le créer
                    if (!is_dir($echeanceFolder)) {
                        if (!mkdir($echeanceFolder, 0775, true)) {
                            return new JsonResponse(['status' => 'error', 'message' => 'Le dossier des échéances ne peut pas être créé.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }
                    list($pdfContent, $facture, $totalPayer) = $this->factureEcheanceService->add($affaire, $request, $documentFolder, $form, $montant, $totalPayer, $montantHt, $applicationRevendeur, $compte);
                
                    // Utiliser le numéro de la facture pour le nom du fichier
                    $filename = $affaire->getCompte()->getIndiceFacture() . '-' . $facture->getNumero() . ".pdf";
                    $pdfPath = '/uploads/APP_'.$this->application->getId().'/factures/echeance/' . $filename;
                    file_put_contents($this->getParameter('kernel.project_dir') . '/public' . $pdfPath, $pdfContent);
                    // Retourner le PDF en réponse

                    if ($totalPayer > $montantHt) {
                        return new JsonResponse(['status' => 'error', 'message' => 'Le total des montants sur les échéances et avances ne doit pas dépasser le montant à payer.'], Response::HTTP_OK);
                        
                    } elseif ($totalPayer < $montantHt)
                    {
                        return new JsonResponse(['status' => 'error', 'message' => 'Le total des montants sur les échéances et avances doit être égale au montant à payer.'], Response::HTTP_OK);
                    }

                    return new JsonResponse([
                        'status' => 'success',
                        'pdfUrl' => $pdfPath,
                    ]);
                    
                }
                
            }

            $data['exception'] = "";
            $data["html"] = $this->renderView('admin/vente/modal_new_echeance.html.twig', [
                'facture' => $facture,
                'affaire' => $affaire,
                'form' => $form->createView(),
                'produits' => $produits,
                'form_affaire' => $form_affaire
            ]);
           
            return new JsonResponse($data);

        } catch (PropertyVideException $PropertyVideException) {
            throw $this->createNotFoundException('Exception' . $PropertyVideException->getMessage());
        }
    }

    #[Route('/nouveau/methode/paiement/{affaireId}', name: '_new_methode_paiement')]
    public function nouveauMethodePaiement(Request $request, $affaireId)
    {
        $affaire = $this->affaireRepo->findOneBy(['id' => $affaireId]);
       
        $products = $this->productService->findProduitAffaire($affaire);

        $montantHt = 0;
        $totalPuHt = 0;
        $totalRemise = 0;

        foreach($products as $product) {
            $puHt = 0;
            $remise = 0;

            if($product->getTypeVente() == "gros" ) {
                $puHt = $product->getPrixVenteGros();
            }else if($product->getTypeVente() == "detail" ) {
                $puHt = $product->getPrixVenteDetail();
            }

            if($product->getRemise()) {
                $remise = $product->getRemise();
            }

            $totalRemise = $totalRemise + $remise;

            $totalPuHt = $totalPuHt + ($puHt * $product->getQtt());

        }

        $montantHt = $totalPuHt - $totalRemise;

        $methodePaiement = new MethodePaiement();

        $form = $this->createForm(MethodePaiementType::class, $methodePaiement);
        $data = [];
        try {

            $form->handleRequest($request);
          
            if ($form->isSubmitted() && $form->isValid()) {
               
                if ($request->isXmlHttpRequest()) {
                    $formData = $form->getData();
                    $this->venteService->add($methodePaiement, $affaire);

                    return $this->render("admin/vente/reload_caisse.html.twig", [
                        'affaire' => $affaire,
                        'montantHt' => $montantHt,
                        'products' => $products

                    ]);
                    //return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
                }

            }

            $data['exception'] = "";
            $data["html"] = $this->renderView('admin/vente/new_methode_paiement.html.twig', [
                'form' => $form->createView(),
                'affaire' => $affaire,
                'montantHt' => $montantHt
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

    #[Route('/update/methode/paiement/{methodeId}', name: '_update_methode_paiement')]
    public function updateMethodePaiement(Request $request, $methodeId)
    {
        $methodePaiement = $this->methodePaiementRepo->findOneBy(['id' => $methodeId]);

        $affaire = $methodePaiement->getAffaire();
       
        $products = $this->productService->findProduitAffaire($affaire);

        $montantHt = 0;
        $totalPuHt = 0;
        $totalRemise = 0;

        foreach($products as $product) {
            $puHt = 0;
            $remise = 0;

            if($product->getTypeVente() == "gros" ) {
                $puHt = $product->getPrixVenteGros();
            }else if($product->getTypeVente() == "detail" ) {
                $puHt = $product->getPrixVenteDetail();
            }

            if($product->getRemise()) {
                $remise = $product->getRemise();
            }

            $totalRemise = $totalRemise + $remise;

            $totalPuHt = $totalPuHt + ($puHt * $product->getQtt());

        }

        $montantHt = $totalPuHt - $totalRemise;

        $form = $this->createForm(MethodePaiementType::class, $methodePaiement);
        $data = [];
        try {

            $form->handleRequest($request);
          
            if ($form->isSubmitted() && $form->isValid()) {
               $this->entityManager->flush();
               return $this->redirectToRoute('ventes_liste_vente');

            }

            $data['exception'] = "";
            $data["html"] = $this->renderView('admin/vente/update_methode_paiement.html.twig', [
                'form' => $form->createView(),
                'affaire' => $affaire,
                'montantHt' => $montantHt,
                'methodePaiement' => $methodePaiement
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
    
}
