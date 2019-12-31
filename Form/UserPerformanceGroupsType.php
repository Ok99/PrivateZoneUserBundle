<?php

namespace Ok99\PrivateZoneCore\UserBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserPerformanceGroupsType extends TextType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'user_performance_groups';
    }
}