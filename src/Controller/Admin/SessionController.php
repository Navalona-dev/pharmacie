<?php

namespace App\Controller\Admin;

use App\Entity\Affaire;
use App\Entity\Session;
use App\Form\SessionType;
use App\Service\AccesService;
use App\Service\SessionService;
use App\Service\ApplicationManager;
use App\Service\ApplicationService;
use App\Repository\SessionRepository;
use App\Exception\PropertyVideException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApplicationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SessionController extends AbstractController
{
    private $applicationService;
    private $accesService;
    private $application;
    private $applicationRepo;
    private $affaireRepo;
    private $entityManager;
    private $sessionService;
    private $sessionRepo;
    
    public function __construct(
        ApplicationService $applicationService, 
        ApplicationManager $applicationManager, 
        AccesService $accesService,
        ApplicationRepository $applicationRepo,
        EntityManagerInterface $entityManager,
        SessionService $sessionService,
        SessionRepository $sessionRepo

        )
    {
        $this->applicationService = $applicationService;
        $this->accesService = $accesService;
        $this->application = $applicationManager->getApplicationActive();
        $this->entityManager = $entityManager;
        $this->sessionService = $sessionService;
        $this->sessionRepo = $sessionRepo;
    }

    #[Route('/admin/session', name: 'app_admin_session')]
    public function index(): Response
    {
        $data = [];
        try {

            $sessions = $this->sessionRepo->findByApplication($this->application->getId());

            $data["html"] = $this->renderView('admin/session/index.html.twig', [
                'listes' => $sessions,
            ]);

            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data["exception"] = $Exception->getMessage();
            $data["html"] = "";
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);

    }
    

    #[Route('/admin/session/rattache/{id}', name: 'app_rattache_session_modal')]
    public function rattacheSession(Session $sessionEntity, Request $request, SessionInterface $session): Response
    {
        try {
            $user = $this->getUser();
            $sessionEntity->addUser($user);
            
            $this->sessionService->persist($sessionEntity);
            $this->sessionService->update();
            $tabIUserInSession = $session->get('allIdUserInSession');
            return $this->redirectToRoute('app_admin', [
                
            ]);

        } catch (PropertyVideException $PropertyVideException) {
            throw $this->createNotFoundException('Exception' . $PropertyVideException->getMessage());
        }
    }

    #[Route('/admin/session/create/ajax', name: 'app_create_session_modal')]
    public function createSession(Request $request, SessionInterface $session): Response
    {
        try {
            $sessionEntity = new Session();

            $now = new \DateTime();
            $dateOnly = $now->format('d-m-Y');
            $nom = 'Session du ' . $dateOnly; 
            
            $user = $this->getUser();
            $sessionEntity->addUser($user);
            $sessionEntity->setApplication($this->application);
            $sessionEntity->setNom($nom);
            $tabIUserInSession = [];
            $sessionEntity = $this->sessionService->add($sessionEntity);
            $session->set('currentSession', $sessionEntity->getId());
            $session->set('dateCurrentSession', $sessionEntity->getDateDebut());
            $session->set('isSessionClose', false);

            return $this->redirectToRoute('app_admin', [
                
            ]);

        } catch (PropertyVideException $PropertyVideException) {
            throw $this->createNotFoundException('Exception' . $PropertyVideException->getMessage());
        }
    }

    #[Route('/admin/session/{id}', name: 'app_session_modal')]
    public function setSession(Session $sessionEntity, Request $request, SessionInterface $session): Response
    {
        try {
            $isNew = $request->get('isNew');
            if ($isNew != null && $isNew == "false") {

                if (null != $session) {
                    if ($sessionEntity != null) {

                        $now = new \DateTime();
                        $hourOnly = $now->format('H:i:s'); 

                        $sessionEntity->setActive(false);
                        $sessionEntity->setClose(true);
                        $sessionEntity->setDateFin(new \DateTime());
                        $sessionEntity->setHeureFin(\DateTime::createFromFormat('H:i:s', $hourOnly));
                        $this->sessionService->persist($sessionEntity);
                        $this->sessionService->update();
                        $session->set('currentSession', null);
                        $session->set('sessionIdClose', $sessionEntity->getId());
                        $session->set('dateCurrentSession', null);
                        $session->set('isSessionClose', true);
                        return new JsonResponse([]);
                    }
                    
                }
                
                return new JsonResponse([]);
            }
            $sessionEntity = new Session();
            $form = $this->createForm(SessionType::class, $sessionEntity);
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()) {  
                    $sessionEntity = $this->sessionService->add($sessionEntity);
                    $tabIUserInSession = [];
                    $session->set('currentSession', $sessionEntity->getId());
                    $session->set('dateCurrentSession', $sessionEntity->getDateDebut());
                    return $this->redirectToRoute('app_admin', [
                        
                    ]);
                
            }

            $data['exception'] = "";
            $data["html"] = $this->renderView('admin/session/modal_new_session.html.twig', [
                'form' => $form->createView(),
            ]);
           
            return new JsonResponse($data);

        } catch (PropertyVideException $PropertyVideException) {
            throw $this->createNotFoundException('Exception' . $PropertyVideException->getMessage());
        }
    }
}
