<?php

namespace Ok99\PrivateZoneCore\UserBundle\Admin;

use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\UserBundle\Admin\Entity\UserAdmin as BaseUserAdmin;

class UserLoginLogAdmin extends BaseUserAdmin
{
    public static $ROLE_ADMIN = 'ROLE_OK99_PRIVATEZONE_USER_LOGIN_LOG_ADMIN_ADMIN';

    protected $baseRouteName = 'admin_privatezonecore_user_login_log';

    protected $baseRoutePattern = 'administrace/login_log';

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_by' => 'createdAt',
        '_sort_order' => 'desc'
    );

    protected $maxPerPage = 100;

    protected $perPageOptions = array(10, 25, 50, 100, 500, 1000);

    public function getBatchActions()
    {
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        return $actions;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'show'));
    }

    protected function configureFormFields(\Sonata\AdminBundle\Form\FormMapper $formMapper)
    {
    }

    // Fields to be shown on lists
    protected function configureListFields(\Sonata\AdminBundle\Datagrid\ListMapper $listMapper)
    {
        $listMapper
            ->add('user', null, array('label' => 'Uživatel', 'admin_code' => 'ok99.privatezone.user.admin.user'))
            ->add('ip', null, array('label' => 'IP'))
            ->add('createdAt', null, array('label' => 'Přihlášen'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                )
            ))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(\Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('user', null, array('label' => 'Uživatel', 'admin_code' => 'ok99.privatezone.user.admin.user'))
            ->add('ip', null, array('label' => 'IP'))
        ;
    }

    // Fields to be shown on revisions
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
        $showMapper
            ->add('user', null, array('label' => 'Uživatel', 'admin_code' => 'ok99.privatezone.user.admin.user'))
            ->add('ip', null, array('label' => 'IP'))
            ->add('ua', null, array('label' => 'User-Agent'))
            ->add('createdAt', null, array('label' => 'Přihlášen'))
        ;
    }
}