<?php

namespace Ok99\PrivateZoneCore\UserBundle\Security;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Ok99\PrivateZoneBundle\Service\ExceptionHandler;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Ok99\PrivateZoneCore\UserBundle\Entity\UserLoginLog;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    protected $requestStack;
    protected $userManager;
    protected $entityManager;
    protected $session;
    protected $exceptionHandler;

    public function __construct(RequestStack $requestStack, UserManagerInterface $userManager, EntityManager $entityManager, Session $session, ExceptionHandler $exceptionHandler)
    {
        $this->requestStack = $requestStack;
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof UserInterface && $user->getUsername() != 'admin') {
            $ip = $this->requestStack->getMasterRequest()->getClientIp();
            $ua = $this->requestStack->getMasterRequest()->headers->get('User-Agent');

            $log = new UserLoginLog();
            $log->setUser($user);
            $log->setIp($ip);
            $log->setUa($ua);

            try {
                $this->entityManager->persist($log);
                $this->entityManager->flush($log);
            } catch(\Exception $e) {
                $this->exceptionHandler->handle($e);
            }

            if (!$user->getEmail()) {
                $this->session->getFlashBag()->add('welcome_redirect_to_user_profile', true);
            }
        }
    }
}