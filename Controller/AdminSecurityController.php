<?php

namespace Ok99\PrivateZoneCore\UserBundle\Controller;

use Sonata\UserBundle\Controller\AdminSecurityController as BaseController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;

class AdminSecurityController extends BaseController
{
    /**
     * @Route("/login", name="sonata_user_admin_security_login")
     * {@inheritdoc}
     */
    public function loginAction()
    {
        $token = $this->container->get('security.token_storage')->getToken();
        $user = $token->getUser();

        if ($user instanceof UserInterface) {
            $request = $this->container->get('request_stack')->getCurrentRequest();

            $response = new RedirectResponse(
                $this->container->get('ok99.privatezone.login_redirect_url_resolver')->resolveRedirectUrl(
                    $request,
                    null,
                ),
            );

            // Přihlášený uživatel nemusí mít SSO cookie (session z doby před SSO, přihlášení přes remember_me,
            // session cookie smazaná zavřením prohlížeče) - bez ní by druhá aplikace přesměrovávala zpět na login dokola.
            // Při impersonaci (switch_user) SSO session nevytváříme, aby se nepřenesla do druhé aplikace.
            if (!$this->container->get('security.authorization_checker')->isGranted('ROLE_PREVIOUS_ADMIN')) {
                $this->container->get('ok99.sso_session_manager')->ensureSession(
                    $request,
                    $response,
                    $user,
                    $token instanceof RememberMeToken,
                );
            }

            return $response;
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