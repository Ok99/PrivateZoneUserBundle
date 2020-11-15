<?php

namespace Ok99\PrivateZoneCore\UserBundle\Admin;

use Knp\Menu\ItemInterface as MenuItemInterface;
use Ok99\PrivateZoneBundle\Service\ClubConfigurationPool;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\UserBundle\Admin\Entity\UserAdmin as BaseUserAdmin;

class UserAddressBookAdmin extends BaseUserAdmin
{
    public static $ROLE_ADMIN = 'ROLE_OK99_PRIVATEZONE_USER_ADDRESS_BOOK_ADMIN_ADMIN';

    protected $baseRouteName = 'admin_privatezonecore_user_address_book';

    protected $baseRoutePattern = 'klub/adresar';

    protected $datagridValues = array(
        '_page' => 1,
        '_sort_by' => 'lastname',
        '_sort_order' => 'asc'
    );

    protected $maxPerPage = 100;

    protected $perPageOptions = array(10, 25, 50, 100, 500, 1000);

    protected $clubConfigurationPool;

    /**
     * UserAddressBookAdmin constructor.
     * @param string $code
     * @param string $class
     * @param string $baseControllerName
     * @param ClubConfigurationPool $clubConfigurationPool
     */
    public function __construct($code, $class, $baseControllerName, ClubConfigurationPool $clubConfigurationPool)
    {
        $this->clubConfigurationPool = $clubConfigurationPool;

        parent::__construct($code, $class, $baseControllerName);
    }

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

    // Fields to be shown on lists
    protected function configureListFields(\Sonata\AdminBundle\Datagrid\ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('avatar', null, array('template' => 'Ok99PrivateZoneUserBundle:UserAddressBookAdmin:list_avatar.html.twig', 'label' => ' '))
            ->addIdentifier('name', null, array(
                'label' => 'User Name',
                'sortable' => true,
                'sort_field_mapping'=> array('fieldName'=>'lastname'),
                'sort_parent_association_mappings' => array(),
                'admin_code' => 'ok99.privatezone.user.admin.user'
            ))
            ->add('regnum', null, array('template' => 'Ok99PrivateZoneUserBundle:UserAdmin:list_regnum.html.twig'))
            ->add('address')
            ->add('email')
            ->add('phone')
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array('template' => 'Ok99PrivateZoneUserBundle:UserAddressBookAdmin:list__action_show.html.twig'),
                    'map' => array('template' => 'Ok99PrivateZoneUserBundle:UserAddressBookAdmin:list__action_map.html.twig'),
                )
            ))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(\Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('firstname')
            ->add('lastname')
            ->add('regnum')
            ->add('nickname')
            ->add('licence')
            ->add('sportident', 'doctrine_orm_callback', [
                'callback' => function(ProxyQueryInterface $queryBuilder, $alias, $field, $value) {
                    if ($value == null || $value['value'] == null) {
                        return;
                    }

                    $queryCondition = sprintf('%s.sportident = :si OR %s.sportident2 = :si OR %s.sportident3 = :si', $alias, $alias, $alias);
                    $queryBuilder->andWhere($queryCondition)->setParameter('si', $value['value']);;
                },
                'operator_type' => 'sonata_type_equal',
            ])
            ->add('street')
            ->add('city')
            ->add('email')
            ->add('phone')
        ;
    }

    // Fields to be shown on revisions
    protected function configureShowFields(\Sonata\AdminBundle\Show\ShowMapper $showMapper)
    {
    }

    /**
     * @inheritdoc
     */
    public function isGranted($name, $object = null)
    {
        if (in_array($name, array('LIST', 'SHOW', 'VIEW'))
            && (
                parent::isGranted('ROLE_OK99_PRIVATEZONE_USER_ADDRESS_BOOK_ADMIN_STAFF')
                ||
                parent::isGranted('ROLE_SUPER_ADMIN')
            )
        ) {
            return true;
        } else {
            return parent::isGranted($name, $object);
        }
    }

    /**
     * @inheritdoc
     */
    public function createQuery($context = 'list')
    {
        $query = parent::createQuery($context);
        $query
            ->andWhere($query->getRootAlias() . '.enabled = :true')
            ->andWhere($query->getRootAlias() . '.regnum <= :maxRegnum')
            ->setParameter('true', true)
            ->setParameter('maxRegnum', 9999)
        ;
        return $query;
    }

    /**
     * Generates the breadcrumbs array
     *
     * Note: the method will be called by the top admin instance (parent => child)
     *
     * @param string                       $action
     * @param \Knp\Menu\ItemInterface|null $menu
     *
     * @return array
     */
    public function buildBreadcrumbs($action, MenuItemInterface $menu = null)
    {
        if (isset($this->breadcrumbs[$action])) {
            return $this->breadcrumbs[$action];
        }

        if (!$menu) {
            $menu = $this->menuFactory->createItem('root');

            $menu = $menu->addChild(
                $this->trans($this->getLabelTranslatorStrategy()->getLabel('dashboard', 'breadcrumb', 'link'), array(), 'Ok99PrivateZoneAdminBundle'),
                array(
                    'uri' => $this->routeGenerator->generate('sonata_admin_dashboard'),
                    'attributes' => array(
                        'icon' => '<i class="fa fa-dashboard"></i>'
                    )
                )
            );
        }

        $menu = $menu->addChild(
            $this->trans($this->getLabel(), array(), $this->translationDomain),
            array('uri' => $this->hasRoute('list') && $this->isGranted('LIST') ? $this->generateUrl('list') : null)
        );

        $childAdmin = $this->getCurrentChildAdmin();

        if ($childAdmin) {
            $id = $this->request->get($this->getIdParameter());

            $menu = $menu->addChild(
                $this->toString($this->getSubject()),
                array('uri' => $this->hasRoute('edit') && $this->isGranted('EDIT') ? $this->generateUrl('edit', array('id' => $id)) : null)
            );

            return $childAdmin->buildBreadcrumbs($action, $menu);

        } elseif ($this->isChild()) {

            if ($action == 'list') {
                $menu->setUri(false);
            } elseif ($action != 'create' && $this->hasSubject()) {
                $menu = $menu->addChild($this->toString($this->getSubject()));
            } else {
                $menu = $menu->addChild(
                    $this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $this->getClassnameLabel(), $action), 'breadcrumb', 'link'))
                );
            }

        } elseif ($action != 'list' && $this->hasSubject()) {
            $menu = $menu->addChild($this->toString($this->getSubject()));
        } elseif ($action != 'list') {
            $menu = $menu->addChild(
                $this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $this->getClassnameLabel(), $action), 'breadcrumb', 'link'))
            );
        }

        return $this->breadcrumbs[$action] = $menu;
    }
}