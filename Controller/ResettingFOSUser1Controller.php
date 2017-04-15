<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ok99\PrivateZoneCore\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ResettingFOSUser1Controller
 *
 * @package Sonata\UserBundle\Controller
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class ResettingFOSUser1Controller extends \Sonata\UserBundle\Controller\ResettingFOSUser1Controller
{
    /**
     * Request reset user password: show form
     *
     * @Route("/resetting/request", name="admin_privatezonecore_user_resetting_request")
     * @return Response
     */
    public function requestAction()
    {
        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array(
            'csrf_token' => $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate'),
            'base_template' => 'Ok99PrivateZoneAdminBundle::standard_layout.html.twig',
            'admin_pool' => $this->container->get('sonata.admin.pool'),
        ));
    }

    /**
     * Request reset user password: submit form and send email
     *
     * @Route("/resetting/send-email", name="admin_privatezonecore_user_resetting_send_email")
     * @return Response
     */
    public function sendEmailAction()
    {
        $username = $this->container->get('request')->request->get('username');

        /** @var $user User */
        $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array(
                filter_var($username, FILTER_VALIDATE_EMAIL) ? 'invalid_email' : 'invalid_username' => $username,
                'csrf_token' => $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate'),
                'base_template' => 'Ok99PrivateZoneAdminBundle::standard_layout.html.twig',
                'admin_pool' => $this->container->get('sonata.admin.pool'),
            ));
        }

        if (null === $user->getEmail()) {
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:request.html.'.$this->getEngine(), array(
                'empty_email' => 1,
                filter_var($username, FILTER_VALIDATE_EMAIL) ? 'invalid_email' : 'invalid_username' => $username,
                'csrf_token' => $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate'),
                'base_template' => 'Ok99PrivateZoneAdminBundle::standard_layout.html.twig',
                'admin_pool' => $this->container->get('sonata.admin.pool'),
            ));
        }

        /*if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:passwordAlreadyRequested.html.'.$this->getEngine(), array(
                'base_template' => 'Ok99PrivateZoneAdminBundle::standard_layout.html.twig',
                'admin_pool' => $this->container->get('sonata.admin.pool'),
            ));
        }*/

        if (null === $user->getConfirmationToken()) {
            /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $this->container->get('session')->set(static::SESSION_EMAIL, $user->getEmail() /*$this->getObfuscatedEmail($user)*/);

        $clubConfigurationPool = $this->container->get('ok99.privatezone.club_configuration_pool');

        $recipients = array();
        if ($user->getEmail()) {
            $recipients[] = $user->getEmail();
        }
        if ($user->getEmailParent() && $user->getAge() < $clubConfigurationPool->getSettings()->getAgeToParentalSupervision()) {
            $recipients[] = $user->getEmailParent();
        }

        try {
            $clubConfigurationPool->getMailer()->send(
                'Ok99PrivateZoneUserBundle:Resetting:email',
                array('no-reply@' . $clubConfigurationPool->getHostName() => $clubConfigurationPool->getAppName()),
                $recipients,
                $this->container->get('translator')->trans('resetting.email.subject', array(), 'FOSUserBundle'),
                array(
                    'confirmationUrl' => $this->container->get('router')->generate('fos_user_resetting_reset', array(
                        'token' => $user->getConfirmationToken()
                    ), true),
                )
            );
        } catch (\Exception $e) {
            $this->container->get('ok99.privatezone.exception_handler')->handle($e);
        }

        $user->setPasswordRequestedAt(new \DateTime());
        $this->container->get('fos_user.user_manager')->updateUser($user);

        return new RedirectResponse($this->container->get('router')->generate('admin_privatezonecore_user_resetting_check_email'));
    }

    /**
     * Tell the user to check his email provider
     *
     * @Route("/resetting/check-email", name="admin_privatezonecore_user_resetting_check_email")
     * @return Response
     */
    public function checkEmailAction()
    {
        $session = $this->container->get('session');
        $email = $session->get(static::SESSION_EMAIL);
        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('admin_privatezonecore_user_resetting_request'));
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:checkEmail.html.'.$this->getEngine(), array(
            'email' => $email,
            'base_template' => 'Ok99PrivateZoneAdminBundle::standard_layout.html.twig',
            'admin_pool' => $this->container->get('sonata.admin.pool'),
        ));
    }

    /**
     * Reset user password
     *
     * @Route("/resetting/reset/{token}", name="admin_privatezonecore_user_resetting_reset")
     * @return Response
     */
    public function resetAction($token)
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
            return new RedirectResponse($this->container->get('router')->generate('admin_privatezonecore_user_resetting_request'));
        }

        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');
        $process = $formHandler->process($user);

        if ($process) {
            $this->setFlash('fos_user_success', 'resetting.flash.success');
            $response = new RedirectResponse($this->getRedirectionUrl($user));
            $this->authenticateUser($user, $response);

            return $response;
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Resetting:reset.html.'.$this->getEngine(), array(
            'token' => $token,
            'form' => $form->createView(),
            'base_template' => 'Ok99PrivateZoneAdminBundle::standard_layout.html.twig',
            'admin_pool' => $this->container->get('sonata.admin.pool'),
        ));
    }

    /**
     * Generate the redirection url when the resetting is completed.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('admin_privatezonecore_user_user_edit', array(
            'id' => $user->getId(),
        ));
    }
}