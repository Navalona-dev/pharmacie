<?php

namespace App\Security;

use App\Entity\Session;
use App\Form\LoginFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Form\FormFactoryInterface;

class UsersAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private $entityManager;

    public function __construct(private UrlGeneratorInterface $urlGenerator, FormFactoryInterface $formFactory, EntityManagerInterface $entityManager)
    {
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
    }

    public function authenticate(Request $request): Passport
    {
        //$email = $request->request->get('email', '');
        $loginForm = $this->formFactory->create(LoginFormType::class);

        $loginForm->handleRequest($request);

        $email = $loginForm->get('email')->getData();

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
       
        return new Passport(
            new UserBadge($email),
            //new PasswordCredentials($request->request->get('password', '')),
            new PasswordCredentials($loginForm->get('password')->getData()),
            [
                //new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new CsrfTokenBadge('authenticate', $request->get('_csrf_token')),
                //new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $sessionEntity = $this->entityManager->getRepository(Session::class)->findOneBy(['isActive' => true]);
        if ($sessionEntity != null) {
            $session->set('currentSession', $sessionEntity->getId());
            $session->set('dateCurrentSession', $sessionEntity->getDateDebut());
        }
        // For example:
        return new RedirectResponse($this->urlGenerator->generate('app_admin'));
        // throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
