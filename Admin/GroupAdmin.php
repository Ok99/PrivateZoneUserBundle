<?php

namespace Ok99\PrivateZoneCore\UserBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\UserBundle\Admin\Entity\GroupAdmin as BaseGroupAdmin;

class GroupAdmin extends BaseGroupAdmin
{
    protected $baseRoutePattern = 'administrace/uzivatelske_skupiny';

    protected $maxPerPage = 50;

    protected $perPageOptions = array(10, 25, 50, 100, 500, 1000);

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_by' => 'name',
        '_sort_order' => 'asc'
    );

    public function configureRoutes(RouteCollection $routes)
    {
        $routes->remove('export');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        parent::configureListFields($listMapper);
        unset($this->listModes['mosaic']);

        $listMapper
            ->add('isDefault', null, array('label' => 'group.is_default','editable' => true))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('isDefault', null, array('label' => 'group.is_default', 'required' => false))
        ;
        parent::configureFormFields($formMapper);
    }

    public function isAdmin($object = null)
    {
        return ($object ? $this->isGranted('ADMIN', $object) : $this->isGranted('ADMIN'))
        || $this->isGranted('ROLE_OK99_PRIVATEZONE_USER_ADMIN_GROUP_ADMIN');
    }

    public function showInAddBlock()
    {
        return true;
    }
}
