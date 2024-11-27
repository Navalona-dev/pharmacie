<?php

namespace App\Controller\Admin;

use App\Form\LoginFormType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/", name="app_login")
     */
    public function login(
        AuthenticationUtils $authenticationUtils

        ): Response
    {
      
            // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        $form = $this->createForm(LoginFormType::class);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'form' => $form->createView(),
        ]);
        
        
    }

    /**
     * @Route("/auth", name="app_auth")
     */
    public function auth(
        AuthenticationUtils $authenticationUtils

        ): Response
    {
      
          

        try {
            
            // if ($this->getUser()) {
            //     return $this->redirectToRoute('target_path');
            // }

            $form = $this->createForm(LoginFormType::class);

            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();
            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();

            $data['exception'] = "";
            $data["html"] = $this->renderView('admin/security/modal_login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'form' => $form->createView(),
        ]);
           
            return new JsonResponse($data);
        } catch (\Exception $Exception) {
            $data['exception'] = $Exception->getMessage();
            $data["html"] = "";
            return new JsonResponse($data);
            $this->createNotFoundException('Exception' . $Exception->getMessage());
        }
        return new JsonResponse($data);
        
        
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
