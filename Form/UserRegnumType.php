<?php

namespace Ok99\PrivateZoneCore\UserBundle\Form;

use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegnumType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array_merge(
            $resolver->getDefinedOptions(),
            [
                'club_shortcut' => null,
            ]
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['club_shortcut'] = $options['club_shortcut'];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'user_regnum';
    }
}