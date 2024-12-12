<?php

namespace App\Controller\Admin;

use App\Entity\Compte;
use App\Entity\Categorie;
use App\Entity\DatePeremption;
use App\Entity\ProduitType;
use App\Service\AccesService;
use App\Service\ExcelImporter;
use App\Entity\ProduitCategorie;
use App\Entity\Stock;
use App\Service\ApplicationManager;
use App\Repository\CompteRepository;
use App\Repository\CategorieRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProduitTypeRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProduitCategorieRepository;
use App\Service\LogService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/import/produit', name: 'imports')]
class ImportProduitController extends AbstractController
{
    private $accesService;
    private $application;
    private $excelImporter;
    private $categorieRepository;
    private $typeRepository;
    private $compteRepository;
    private $em;
    private $produitCategorieRepo;
    private $logService;

    public function __construct(
        ApplicationManager $applicationManager, 
        AccesService $accesService,
        ExcelImporter $excelImporter,
        CategorieRepository $categorieRepository,
        ProduitTypeRepository $typeRepository,
        CompteRepository $compteRepository,
        EntityManagerInterface $em,
        ProduitCategorieRepository $produitCategorieRepo,
        LogService $logService)
    {
        $this->accesService = $accesService;
        $this->application = $applicationManager->getApplicationActive();
        $this->excelImporter = $excelImporter;
        $this->categorieRepository = $categorieRepository;
        $this->typeRepository = $typeRepository;
        $this->compteRepository = $compteRepository;
        $this->em = $em;
        $this->produitCategorieRepo = $produitCategorieRepo;
        $this->logService = $logService;
    }

    #[Route('/', name: '_liste')]
    public function index(Request $request): Response
    {
        $data = [];
        ini_set('memory_limit', '512M'); // Augmenter la limite de mémoire
        try {
            
            if ($request->files->get('file')) {
                $file = $request->files->get('file');
                $spreadsheet = IOFactory::load($file);

                $sheet = $spreadsheet->getActiveSheet();
              
                foreach ($sheet->getRowIterator() as $index => $row) {
                    if ($index == 1) {
                        continue;
                    }
    
                    $dataProduct = [];
    
                    foreach ($row->getCellIterator() as $cell) {
                        $dataProduct[] = $cell->getValue();
                    }
                    $date = new \DateTime();

                    // Traiter la catégorie
                    $dataCategorie = isset($dataProduct[0]) ? trim($dataProduct[0]) : null;
                    $dataCategorie = trim($dataCategorie);
                    $nameCategorie = $dataCategorie;
                    if($dataCategorie == null) {
                      $nameCategorie = 'Autre' ;  
                    } 
                    $existingCategorie = $this->categorieRepository->findOneBy(['nom' => $nameCategorie, 'application' => $this->application]);
                    
                    if ($dataCategorie !== null) {
                       
                        // Vérifie si $categorieName est dans $categories
                 
                        if ($existingCategorie == null) {
                            $categorie = null;
                            // Si la catégorie n'existe pas dans la base de données
                            $categorie = new Categorie();
                           
                            $categorie->setNom($nameCategorie);
                            $categorie->setDateCreation($date);
                            $categorie->setApplication($this->application);
                            $this->em->persist($categorie);
                        } else {
                            $categorie = $existingCategorie;
                        }
                        
                    }
                    //$this->em->flush();
                    //dd($existingCategorie, $categorie);

                    //traiter le type
                    $dataType = isset($dataProduct[2]) ? trim($dataProduct[1]) : null;
                    $nameType = $dataType;
                    if($dataType == null) {
                        $nameType = "Autre";
                    }
                    $existingType = $this->typeRepository->findOneBy(['nom' => $nameType, 'application' => $this->application]);
                    
                    if($dataType !== null) {

                        $type = null;
    
                        //si le type existe déjà dans la base de données
                        if ($existingType == null) {
                            $type = new ProduitType();
                            
                            $type->setNom($nameType);
                            $type->setDateCreation($date);
                            $type->setApplication($this->application);
                            $type->setIsActive(true);
                            $this->em->persist($type);
                        } else {
                            $type = $existingType;
                        }
                    }
                   
                    //traiter le produit
                    $dataReference = isset($dataProduct[4]) ? trim($dataProduct[2]) : null;
                    
                    $existingProduitCategorie = $this->produitCategorieRepo->findOneBy(['reference' => $dataReference, 'application' => $this->application]);
                   
                    if($dataReference !== null) {
                        $produitCategorie = null;
                        $stockRestant = 0;

                        if ($existingProduitCategorie == null) {
                            $produitCategorie = new ProduitCategorie();
    
                            $produitCategorie->setCategorie($categorie ?? null);
                            $produitCategorie->setType($type ?? null);
                            $produitCategorie->setNom($dataProduct[3]);
                            $produitCategorie->setReference($dataProduct[2] ?? null);
                            $produitCategorie->setPresentationGros($dataProduct[4] ?? null);
                            $produitCategorie->setUniteVenteGros($dataProduct[5] ?? null);
                            $produitCategorie->setVolumeGros(floatval($dataProduct[6] ?? 1.0));
                            $produitCategorie->setPrixAchat(floatval($dataProduct[7] ?? 0.0));
                            
                            $produitCategorie->setPresentationDetail($dataProduct[8]);
                            $produitCategorie->setUniteVenteDetail($dataProduct[9]);
                            $produitCategorie->setVolumeDetail(floatval($dataProduct[10]));
                            $produitCategorie->setApplication($this->application);
            
                            $produitCategorie->setDateCreation($date);
                            $produitCategorie->setStockMin(10);
                            $produitCategorie->setStockMax(50);

                            //gerer les stocks, fournisseurs et dateperemption
                            $stocksData = isset($dataProduct[11]) ? explode('|', $dataProduct[11]) : [];
                            $datesPeremption = isset($dataProduct[12]) ? explode(',', $dataProduct[12]) : []; // Champs date séparés par des virgules
                            $fournisseurs = isset($dataProduct[13]) ? explode(',', $dataProduct[13]) : [];
                            $telephones = isset($dataProduct[14]) ? explode(',', $dataProduct[14]) : [];
                            $nomenclatures = isset($dataProduct[15]) ? explode(',', $dataProduct[15]) : [];
                            
                            $pourcentagesVente = isset($dataProduct[16]) ? explode('|', $dataProduct[16]) : [];
                            $prixAchat = floatval($dataProduct[7] ?? 0.0);
                            // Convertir chaque pourcentage en flottant avant de trouver le maximum
                            $pourcentagesVente = array_map(function($value) {
                                return str_replace(',', '.', $value); // Remplacer les virgules par des points
                            }, $pourcentagesVente);
                            
                            // Convertir en flottant et trouver le maximum
                            $pourcentagesVenteNumeriques = array_map('floatval', $pourcentagesVente);
                            $maxPourcentageVente = max($pourcentagesVenteNumeriques);
                            

                            // Calcul du montant à ajouter basé sur le prix d'achat et le pourcentage
                            $montantAdditionnel = ($prixAchat * $maxPourcentageVente) / 100;

                            // Calcul du prix de vente gros et détail
                            $prixVenteGros = $prixAchat + $montantAdditionnel;
                            $prixVenteDetail = $prixVenteGros;

                            foreach ($stocksData as $key => $stockValue) {
                                //1-date peremption

                                $datePeremptionValue = isset($datesPeremption[$key]) ? trim($datesPeremption[$key]) : null;
                                $datePeremption = null;
                                if ($datePeremptionValue) {
                                    $datePeremption = new DatePeremption();
                                    $datePeremption->setDate(\DateTime::createFromFormat('d/m/Y', $datePeremptionValue));
                                    $datePeremption->setDateCreation($date);
                                    $this->em->persist($datePeremption);
                                }

                                //2-fournisseur
                                $fournisseurValue = isset($fournisseurs[$key]) ? trim($fournisseurs[$key]) : null;
                                $telephoneValue = isset($telephones[$key]) ? trim($telephones[$key]) : null;
                                $nomenclatureValue = isset($nomenclatures[$key]) ? trim($nomenclatures[$key]) : null;

                                $fournisseur = null;

                                if ($nomenclatureValue) {
                                    // Vérifier si le fournisseur existe déjà par sa nomenclature
                                    $fournisseur = $this->em->getRepository(Compte::class)->findOneBy(['code' => $nomenclatureValue, 'application' => $this->application]);
                                
                                    if (!$fournisseur) {

                                        // Si le fournisseur n'existe pas, on le crée
                                        $fournisseur = new Compte();
                                        $fournisseur->setNom($fournisseurValue);
                                        $fournisseur->setGenre(2); // Exemple : Genre spécifique pour les fournisseurs
                                        $fournisseur->setDateCreation(new \DateTime());
                                        $fournisseur->setTelephone($telephoneValue);
                                        $fournisseur->setCode($nomenclatureValue);
                                        $fournisseur->setApplication($this->application);
                                        $this->em->persist($fournisseur);

                                    }
                                }

                                //3-stocks
                                $stock = new Stock();
                            
                                $pourcentageVente = isset($pourcentagesVente[$key]) ? trim($pourcentagesVente[$key]) : null;
                                $stock->setQtt(floatval($stockValue));
                                $stock->setProduitCategorie($produitCategorie); 
                                $stock->setDateCreation(new \DateTime()); 
                                $stock->setQttRestant(floatval($stockValue));
                                $stock->setPourcentageVente(floatval($pourcentagesVente));
                            
                                $stock->setCompte($fournisseur);
                                $stock->setDatePeremption($datePeremption);
                                $this->em->persist($stock);

                                $stockRestant += $stock->getQtt();
                                    
                            }

                            $produitCategorie->setPrixVenteGros($prixVenteGros);
                            $produitCategorie->setMaxPourcentage($maxPourcentageVente);
                            $produitCategorie->setPrixVenteDetail($prixVenteDetail);
                            $produitCategorie->setStockRestant($stockRestant);
                            $this->em->persist($produitCategorie);
                            //dd($produitCategorie,$prixVenteGros);

                            
                        } else {
                            $produitCategorie = $existingProduitCategorie;
                        }

                        // Format stock
                            $qtt = floatval($stockRestant);
                            $stockRestant = intval($qtt); // Conversion en entier pour la partie entière du stock
                            $sousUnite = 0;

                            // Séparer la partie entière et décimale sans utiliser number_format
                            $decimalPart = $qtt - $stockRestant; // Calculer la partie décimale

                            if ($decimalPart > 0) {
                                // Si la partie décimale est non nulle
                                $sousUnite = $decimalPart * $produitCategorie->getVolumeGros(); // Calculer la sous-unité
                            }

                            // Préparation de la chaîne de résultat
                            $stockRestantStr = $stockRestant . " " . $produitCategorie->getPresentationGros(); // Chaîne pour l'affichage de la partie entière

                            if ($sousUnite > 0) {
                                $stockRestantStr .= " et " . $sousUnite . " " . $produitCategorie->getUniteVenteGros(); // Ajout de la sous-unité si nécessaire
                            }

                    //// End format stock
                       
                        $user = $this->getUser();
                        $data["produit"] = $produitCategorie->getNom();
                        $data["dateReception"] = (new \DateTime())->format("d-m-Y h:i:s");
                        $data["dateTransfert"] = null;
                        $data["dateSortie"] = null;
                        $data["userDoAction"] = $user->getUserIdentifier();
                        $data["source"] = "Import";
                        $data["destination"] = $this->application->getEntreprise();
                        $data["action"] = "Ajout";
                        $data["type"] = "Ajout";
                        $data["qtt"] = $stockRestantStr;
                        $data["stockRestant"] = $stockRestantStr;
                        $data["fournisseur"] = ($produitCategorie->getReference() != false && $produitCategorie->getReference() != null ? $produitCategorie->getReference() : null);
                        $data["typeSource"] = "Fichier";
                        $data["typeDestination"] = "Point de vente";;
                        $data["commande"] = null;
                        $data["commandeId"] = null;
                        $data["sourceId"] =  null;
                        $data["destinationId"] = $this->application->getId();
                        $this->logService->addLog($request, "Import", $this->application->getId(), $produitCategorie->getReference(), $data);

                    }
                    $this->em->flush();
                }
                
                $this->addFlash('success', 'Importation produit avec succès.');
                return $this->redirect("/admin#tab-produit-categorie");
            }

           
            $data["html"] = $this->renderView('admin/import_produit/index.html.twig', [
                
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
