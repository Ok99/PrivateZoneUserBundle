<?php

namespace Ok99\PrivateZoneCore\UserBundle\Security;

class UserProvider extends \FOS\UserBundle\Security\UserProvider
{

    public function loadUserByUsername($username)
    {
        if (strlen($username) === 7) {
            $username = substr($username, 3);
        }

        return parent::loadUserByUsername($username);
    }

}