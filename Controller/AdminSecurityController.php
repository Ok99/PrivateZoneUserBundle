<?php

namespace Ok99\PrivateZoneCore\UserBundle\Controller;

use Sonata\UserBundle\Controller\AdminSecurityController as BaseController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Model\UserInterface;

class AdminSecurityController extends BaseController
{
    /**
     * @Route("/login", name="sonata_user_admin_security_login")
     * {@inheritdoc}
     */
    public function loginAction()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        if ($user instanceof UserInterface) {
            $url = $this->container->get('router')->generate('sonata_admin_dashboard');
            return new RedirectResponse($url);
        }

        return parent::loginAction();
    }

    /**
     * @Route("/login_check", name="sonata_user_admin_security_check")
     * {@inheritdoc}
     */
    public function checkAction()
    {
        $url = $this->container->get('router')->generate('sonata_admin_dashboard');
        return new RedirectResponse($url);
    }
}