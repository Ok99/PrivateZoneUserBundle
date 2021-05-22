<?php
/**
 * Created by PhpStorm.
 * User: tomas.vitek
 * Date: 22.05.2021
 * Time: 16:03
 */

namespace Ok99\PrivateZoneCore\UserBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserSportLicencesType extends TextType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'user_sport_licences';
    }
}
