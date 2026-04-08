<?php

namespace Ok99\PrivateZoneCore\UserBundle\Security;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserProvider extends \FOS\UserBundle\Security\UserProvider
{

    public function loadUserByUsername($username)
    {
        try {
            return parent::loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            if (strlen($username) !== 7) {
                throw $e;
            }
        }

        if (strlen($username) === 7) {
            $username = substr($username, 3);
        }

        return parent::loadUserByUsername($username);
    }

}