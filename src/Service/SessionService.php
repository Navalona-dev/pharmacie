<?php
namespace App\Service;

use App\Service\AuthorizationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Exception\PropertyVideException;
use App\Exception\ActionInvalideException;
use App\Entity\Privilege;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

class SessionService
{
    private $tokenStorage;
    private $authorization;
    private $entityManager;
    private $session;
    public  $isCurrentDossier = false;

    public function __construct(AuthorizationManager $authorization, TokenStorageInterface  $TokenStorageInterface, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $TokenStorageInterface;
        $this->authorization = $authorization;
        $this->entityManager = $entityManager;
    }

    public function find($id)
    {
        return $this->entityManager->getRepository(Session::class)->find($id);
    }

    public function persist($entity)
    {
        $this->entityManager->persist($entity);
    }
    
    public function add($instance)
    {
        $datetime = new \DateTime();
        $instance->setDescription(null);
        $instance->setDateDebut($datetime);
        $instance->setHeureDebut($datetime);
        $instance->setActive(true);
        $this->entityManager->persist($instance);
        $this->update();
        
        return $instance;
    }

    public function update()
    {
        $this->entityManager->flush();
    }

    public function remove($session)
    {
        $this->entityManager->remove($session);
        $this->update();
    }

    

}