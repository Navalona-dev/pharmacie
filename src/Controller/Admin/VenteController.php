<?php

namespace App\Controller\Admin;

use App\Service\AccesService;
use App\Service\ApplicationManager;
use App\Service\ApplicationService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/vente', name: 'ventes')]
class VenteController extends AbstractController
{
    private $applicationService;
    private $accesService;
    private $application;
    
    public function __construct(ApplicationService $applicationService, ApplicationManager $applicationManager, AccesService $accesService)
    {
        $this->applicationService = $applicationService;
        $this->accesService = $accesService;
        $this->application = $applicationManager->getApplicationActive();
    }
    
    #[Route('/', name: '_liste')]
    public function index(): Response
    {
        $data = [];
        try {
            
           
            $data["html"] = $this->renderView('admin/vente/index.html.twig', [
                //'listes' => $categories,
            ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
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
}
