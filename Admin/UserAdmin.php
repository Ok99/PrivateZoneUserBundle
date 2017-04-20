<?php

namespace Ok99\PrivateZoneCore\UserBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Ok99\PrivateZoneBundle\AdminInterface\ExportAdminInterface;
use Ok99\PrivateZoneBundle\Service\ClubConfigurationPool;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\UserBundle\Admin\Entity\UserAdmin as BaseUserAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserAdmin extends BaseUserAdmin implements ExportAdminInterface
{
    public static $ROLE_ADMIN = 'ROLE_OK99_PRIVATEZONE_USER_ADMIN_USER_ADMIN';

    protected $baseRoutePattern = 'klub/uzivatele';

    protected $maxPerPage = 50;

    protected $perPageOptions = array(10, 25, 50, 100, 500, 1000);

    /** @var ContainerInterface */
    protected $container;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ClubConfigurationPool */
    protected $clubConfigurationPool;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setClubConfigurationPool(ClubConfigurationPool $clubConfigurationPool)
    {
        $this->clubConfigurationPool = $clubConfigurationPool;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'create', 'edit', 'export'));
        $collection->add('store_cropped_avatar', 'store-cropped-avatar');
        $collection->add('store_property', 'store-property');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $now = new \DateTime();

        $trainingGroupsQuery = $this->entityManager->getRepository('Ok99PrivateZoneBundle:TrainingGroup')->getGroupsQuery($this->getRequest()->getLocale());
        $userGroupsQuery = $this->entityManager->getRepository('Ok99PrivateZoneUserBundle:Group')->getGroupsQuery();

        $formMapper
            ->tab('User')
                ->with('Basic', array('class' => 'col-md-6'))
                    ->add('firstname', null, array('required' => true))
                    ->add('lastname', null, array('required' => true))
                    ->add('nickname', null, array('required' => false))
                    ->add('dateOfBirth', 'sonata_type_date_picker', array(
                        'years' => range(1900, $now->format('Y')),
                        'dp_pick_time' => false,
                        'dp_min_date' => '1/1/1900',
                        'dp_max_date' => $now->format('j/n/Y'),
                        //'dp_default_date' => '1/1/'.($now->format('Y')-25),
                        'required' => true
                    ))
                    /*->add('gender', 'sonata_user_gender', array(
                        'required' => true,
                        'translation_domain' => $this->getTranslationDomain()
                    ))*/
                    ->add('avatar', 'user_avatar', array('label' => 'User.Avatar', 'required' => false,))
                    ->add('photo', 'user_photo', array('label' => 'User.Photo.Addressbook', 'required' => false))
                ->end()
                ->with('Contact', array('class' => 'col-md-6'))
                    ->add('email')
                    ->add('phone', null, array('required' => false))
                    ->add('street', null, array('required' => false))
                    ->add('city', null, array('required' => false))
                    //->add('zip', null, array('required' => false))
                ->end()
                ->with('Registration', array('class' => 'col-md-6'))
                    ->add('regnum', null, array('required' => true, 'read_only' => !$this->isAdmin(), 'attr' => array('onkeyup' => '$("input[name=\""+$(this).attr("name").substr(0, $(this).attr("name").indexOf("["))+"[username]\"]").val($(this).val())')))
                    //->add('licence', null, array('required' => false))
                    ->add('sportident', null, array('required' => false))
                    ->add('trainingGroups', 'sonata_type_model', array(
                        'required' => false,
                        'expanded' => true,
                        'multiple' => true,
                        'btn_add'  => false,
                        'query'    => $trainingGroupsQuery
                    ))
                    ->add('suggestEventClasses', null, array('required' => false))
                ->end();

                if ($this->getSubject()->getAge() < $this->clubConfigurationPool->getSettings()->getAgeToParentalSupervision()) {
                    $formMapper->with('Parent', array('class' => 'col-md-6'))
                        ->add('emailParent', null, array('label' => 'Email'))
                        ->add('phoneParent', null, array('required' => false, 'label' => 'Phone'))
                    ->end();
                }

                $formMapper->with('User', array('class' => 'col-md-6'))
                    ->add('username', null, array('required' => false, 'read_only' => true))
                    ->add('plainPassword', 'text', array(
                        'required' => (!$this->getSubject() || is_null($this->getSubject()->getId()))
                    ));
                    if ($this->isAdmin()) {
                        $formMapper->add('groups', 'sonata_type_model', array(
                            'required' => false,
                            'expanded' => true,
                            'multiple' => true,
                            'btn_add' => false,
                            'query'    => $userGroupsQuery
                        ));
                    }
                $formMapper->end()
            ->end()
        ;

        if ($this->isAdmin()) {
            $formMapper
                ->with('Status', array('class' => 'col-md-6'))
                    ->add('enabled', null, array('required' => false))
                    ->add('sponsor', null, array('required' => false))
                ->end()
            ;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
//            $formMapper->with('Roles')
//                ->add('realRoles', 'sonata_security_roles', array(
//                    'label'    => 'form.label_roles',
//                    'expanded' => true,
//                    'multiple' => true,
//                    'required' => false
//                ))
//            ->end();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
            ->add('firstname')
            ->add('lastname')
            ->add('regnum')
            ->add('licence')
            ->add('groups')
            //->add('gender')
            ->add('enabled')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        unset($this->listModes['mosaic']);

        $listMapper
            ->addIdentifier('name', null, array('label' => 'User Name'))
            ->add('regnum', null, array('template' => 'Ok99PrivateZoneUserBundle:UserAdmin:list_regnum.html.twig'))
            ->add('groups', null, array('template' => 'Ok99PrivateZoneUserBundle:UserAdmin:list_groups_field.html.twig'))
            ->add('enabled', null, array('editable' => true))
        ;

        if ($this->isGranted('ROLE_SUPER_ADMIN')/*$this->isGranted('ROLE_ALLOWED_TO_SWITCH')*/) {
            $listMapper
                ->add('impersonating', 'string', array('template' => 'SonataUserBundle:Admin:Field/impersonating.html.twig'))
            ;
        }

        if ($this->isAdmin()) {
            $listMapper->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                )
            ));
        }
    }

    public function isAdmin($object = null)
    {
        return $this->isGranted(self::$ROLE_ADMIN) || ($object ? $this->isGranted('ADMIN', $object) : $this->isGranted('ADMIN'));
    }

    /**
     * {@inheritdoc}
     * @var User $object
     */
    public function isGranted($name, $object = null)
    {
        switch($name) {
            case 'ROLE_SUPER_ADMIN':
                break;
            default:
                $isAdmin =
                    (!$object && parent::isGranted('ADMIN'))
                    ||
                    ($object && parent::isGranted('ADMIN', $object))
                    ||
                    parent::isGranted(self::$ROLE_ADMIN)
                    ||
                    parent::isGranted('ROLE_SUPER_ADMIN');

                if ($isAdmin) {
                    return true;
                }
        }

        switch($name) {
            case 'CREATE':
                if (!$this->isAdmin()) {
                    return false;
                }
                break;
            case 'EDIT':
                if ($object && $this->container->get('security.token_storage')->getToken()->getUser()->getId() == $object->getId()) {
                    return true;
                }
                break;
        }

        return parent::isGranted($name, $object);
    }

    public function showAddBtnInDashboard()
    {
        return $this->isAdmin();
    }

    public function showListBtnInDashboard()
    {
        return $this->isAdmin();
    }

    public function showInAddBlock()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getFormTheme()
    {
        $formTheme = parent::getFormTheme();
        return array_merge($formTheme, array('Ok99PrivateZoneUserBundle:UserAdmin:admin_fields.html.twig'));
    }

    public function createQuery($context = 'list')
    {
        /** @var QueryBuilder $query */
        $query = parent::createQuery($context);
        $query->addOrderBy($query->getRootAlias() . '.lastname', 'asc');
        $query->addOrderBy($query->getRootAlias() . '.firstname', 'asc');
        $query->addOrderBy($query->getRootAlias() . '.regnum', 'asc');

        if ($context == 'list') {
            if (!$this->isAdmin()) {
                $user = $this->container->get('security.context')->getToken()->getUser();
                $query->andWhere($query->getRootAlias() . '.id = :userId');
                $query->setParameter('userId', $user->getId());

                $object = $query->getQuery()->getSingleResult();
                $url = $this->generateUrl('edit', array('id' => $object->getId()));
                header('Location: ' . $url);
                exit;
            }

            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $query->andWhere($query->getRootAlias() . '.regnum <= :maxRegnum');
                $query->setParameter('maxRegnum', 9999);
            }
        }

        return $query;
    }

    public function prePersist($object)
    {
        $object->setUsername($object->getRegnum());
    }

    public function preUpdate($object)
    {
        parent::preUpdate($object);
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

    /** EXPORT */

    /**
     * @inheritdoc
     */
    public function getExportName() {
        return 'Clenove';
    }

    /**
     * @inheritdoc
     */
    public function getExportFormats() {
        return array(
            'xlsx',
        );
    }

    /**
     * @inheritdoc
     */
    public function getExportFields() {
        return array(
            'Jméno' => 'firstname',
            'Příjmení' => 'lastname',
            'Reg. číslo' => 'regnum',
            'Licence' => 'licence',
            'SportIdent' => 'sportident',
            'Datum narození' => 'date_of_birth',
            'Email' => 'email',
            'Telefon' => 'phone',
            'Ulice' => 'street',
            'Město' => 'city',
        );
    }

    /**
     * @inheritdoc
     */
    public function exportFieldRender($field, $value)
    {
        switch($field) {
            case 'date_of_birth':
                return $value ? (new \DateTime($value))->format('j. n. Y') : '';
                break;
            case 'regnum':
                return strtoupper($this->getConfigurationPool()->getContainer()->getParameter('ok99.privatezone.club_shortcut')) . $value;
                break;
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function exportQueryModify(ProxyQueryInterface $query)
    {
        $query
            ->andWhere($query->getRootAlias() . '.regnum <= :maxRegnum')
            ->setParameter('maxRegnum', 9999)
        ;

        return $query;
    }
}
