<?php

namespace Ok99\PrivateZoneCore\UserBundle\Admin;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Ok99\PrivateZoneBundle\AdminInterface\ExportAdminInterface;
use Ok99\PrivateZoneBundle\Service\ClubConfigurationPool;
use Ok99\PrivateZoneCore\ClassificationBundle\Entity\Category;
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
    const EXPORT_TYPE_COMMON = 'common';
    const EXPORT_TYPE_DIFF = 'diff';

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

    /** @var string */
    protected $exportType;

    /** @var string */
    protected $exportName;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param ClubConfigurationPool $clubConfigurationPool
     */
    public function setClubConfigurationPool(ClubConfigurationPool $clubConfigurationPool)
    {
        $this->clubConfigurationPool = $clubConfigurationPool;
    }

    /**
     * @return string
     */
    public function getExportType()
    {
        return $this->exportType;
    }

    /**
     * @param string $exportType
     */
    public function setExportType($exportType)
    {
        $this->exportType = $exportType;
    }

    /**
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'create', 'edit', 'export', 'delete'));
        $collection->add('store_cropped_avatar', 'store-cropped-avatar/{userId}');
        $collection->add('store_property', 'store-property');
        $collection->add('year_diff', 'export-zmen-osobnich-udaju/{year}');
    }

    /**
     * @return array
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();
        unset($actions['delete']);
        return $actions;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $now = new \DateTime();

        /** @var ClubConfigurationPool $clubConfigurationPool */
        $clubConfigurationPool = $this->getConfigurationPool()->getContainer()->get('ok99.privatezone.club_configuration_pool');

        /** @var User $user */
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $userGroupsQuery = $this->entityManager->getRepository('Ok99PrivateZoneUserBundle:Group')->getGroupsQuery();

        $eventSportChoices = [];
        foreach ($this->entityManager->getRepository('Ok99PrivateZoneBundle:EventSport')->getActiveSports() as $eventSport) {
            $eventSportChoices[$eventSport->getId()] = $eventSport;
        }
        ksort($eventSportChoices);

        $eventSportidentTypeChoices = [];
        foreach ($this->entityManager->getRepository('Ok99PrivateZoneBundle:EventSportidentType')->getActiveSportidentTypes() as $eventSportidentType) {
            $eventSportidentTypeChoices[$eventSportidentType->getId()] = $eventSportidentType;
        }
        ksort($eventSportidentTypeChoices);

        $formMapper->tab('User')
            ->with('Basic', array('class' => 'col-md-6'));

                $formMapper->add('sportLicencesSorted', 'user_sport_licences', array(
                    'label' => 'User.Sports',
                    'required' => false
                ));

                if ($this->getSubject()->getPerformanceGroups()) {
                    $formMapper->add('performanceGroups', 'user_performance_groups', array(
                        'label' => 'User.PerformanceGroups',
                        'required' => false
                    ));
                }
                if ($this->getSubject()->getTrainingGroups()) {
                    $formMapper->add('trainingGroups', 'user_training_groups', array(
                        'label' => 'User.TrainingGroups',
                        'required' => false
                    ));
                }

                $formMapper
                ->add('regnum', null, array('required' => false, 'disabled' => $this->id($this->getSubject()), 'attr' => array('onkeyup' => '$("input[name=\""+$(this).attr("name").substr(0, $(this).attr("name").indexOf("["))+"[username]\"]").val($(this).val())')))
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
                ));

            if (
                $clubConfigurationPool->getClubShortcutLower() === 'phk' &&
                (
                    $this->getSubject()->hasRole('ROLE_SUPER_ADMIN') ||
                    $this->getSubject()->hasRole('ROLE_OK99_PRIVATEZONE_NEWS_ADMIN_POST_ADMIN') ||
                    $this->getSubject()->hasRole('ROLE_OK99_PRIVATEZONE_NEWS_ADMIN_POST_EDITOR')
                )
            ) {
                $formMapper
                    ->add('description', null, array(
                        'help' => 'Description Help',
                        'required' => false,
                    ));
            }

            $formMapper
                ->add('suggestEventClasses', null, array('required' => false))
                /*->add('gender', 'sonata_user_gender', array(
                    'required' => true,
                    'translation_domain' => $this->getTranslationDomain()
                ))*/
            ->end();

            $formMapper->with('SportIdent', array('class' => 'col-md-6'))
                ->add('sportidentSport', null, array(
                    'choices' => $eventSportChoices,
                    'placeholder' => $this->translator->trans('sportident_sport_placeholder', [], 'SonataUserBundle'),
                ))
                ->add('sportidentType', null, array(
                    'choices' => $eventSportidentTypeChoices,
                    'placeholder' => $this->translator->trans('sportident_type_placeholder', [], 'SonataUserBundle'),
                ))
                ->add('sportident', null, array('required' => false))

                ->add('sportident2Sport', null, array(
                    'label' => 'Sportident Sport',
                    'choices' => $eventSportChoices,
                    'placeholder' => $this->translator->trans('sportident_sport_placeholder', [], 'SonataUserBundle'),
                ))
                ->add('sportident2Type', null, array(
                    'label' => 'Sportident Type',
                    'choices' => $eventSportidentTypeChoices,
                    'placeholder' => $this->translator->trans('sportident_type_placeholder', [], 'SonataUserBundle'),
                ))
                ->add('sportident2', null, array('required' => false, 'label' => 'Sportident'))

                ->add('sportident3Sport', null, array(
                    'label' => 'Sportident Sport',
                    'choices' => $eventSportChoices,
                    'placeholder' => $this->translator->trans('sportident_sport_placeholder', [], 'SonataUserBundle'),
                ))
                ->add('sportident3Type', null, array(
                    'label' => 'Sportident Type',
                    'choices' => $eventSportidentTypeChoices,
                    'placeholder' => $this->translator->trans('sportident_type_placeholder', [], 'SonataUserBundle'),
                ))
                ->add('sportident3', null, array('required' => false, 'label' => 'Sportident'))
            ->end();

            $formMapper->with('Contact', array('class' => 'col-md-6'))
                ->add('email')
                ->add('phone', null, array('required' => false))
                ->add('street', null, array('required' => false))
                ->add('city', null, array('required' => false))
                ->add('zip', null, array('required' => false))
            ->end();

            if ($this->getSubject()->getAge() < $this->clubConfigurationPool->getSettings()->getAgeToParentalSupervision()) {
                $formMapper->with('Parent', array('class' => 'col-md-6'))
                    ->add('emailParent', null, array('label' => 'Parent Emails'))
                    ->add('phoneParent', null, array('required' => false, 'label' => 'Phone'))
                ->end();
            }

            $formMapper->with('User', array('class' => 'col-md-6'));
                if ($this->isAdmin()) {
                    $formMapper
                        ->add('enabled', null, array(
                            'required' => false,
                            'help' => '<i class="fa fa-warning text-yellow"></i> Deaktivace touto cestou zabrání pouze tomu, aby se člen mohl do systému přihlásit.<br/>Pro úplnou deaktivaci použijte tlačítko "Deaktivovat a vymazat osobní údaje".',
                        ))
                        ->add('sponsor', null, array('required' => false));
                }
                $formMapper
                    ->add('username', null, array('required' => false, 'read_only' => true))
                    ->add('plainPassword', 'text', array(
                        'required' => (!$this->getSubject() || is_null($this->getSubject()->getId()))
                    ));
                if ($this->isAdmin()) {
                    $formMapper
                        ->add('groups', 'sonata_type_model', array(
                            'required' => false,
                            'expanded' => true,
                            'multiple' => true,
                            'btn_add'  => false,
                            'query'    => $userGroupsQuery
                        ));
                }
            $formMapper->end();

            $formMapper->with('Photo', array('class' => 'col-md-6'))
                ->add('avatar', $this->id($this->getSubject()) ? 'user_avatar' : 'hidden', array('label' => 'User.Avatar', 'required' => false))
                ->add('photo', $this->id($this->getSubject()) ? 'user_photo' : 'hidden', array('label' => 'User.Photo.Addressbook', 'required' => false))
            ->end();


            if ($clubConfigurationPool->useEventEntryDateNotifications() && $clubConfigurationPool->getSettings()->getNotifyEventEntryDates()) {
                if (!$this->subject->getAmountOfDaysBeforeEventEntryDateToNotify()) {
                    $this->subject->setAmountOfDaysBeforeEventEntryDateToNotify($clubConfigurationPool->getSettings()->getAmountOfDaysBeforeEventEntryDateToNotify());
                }

                $formMapper->with('EntryDatesNotifications', array('class' => 'col-md-6'))
                    ->add('notifyEventEntryDates', null, array('label' => 'Chci dostávat upozornění na blížící se termíny přihlášek', 'required' => false))
                    ->add('notifyFirstEventEntryDateOnly', null, array('label' => 'Dostávat upozornění pouze na první termín přihlášek', 'required' => false))
                    ->add('amountOfDaysBeforeEventEntryDateToNotify', null, array('label' => 'Oznamuj mi termíny přihlášek počet dní před jejich vypršením', 'required' => false))
                    ->add('notifyEventSports', null, array('label' => 'Chci dostávat upozornění pouze u sekcí', 'required' => false))
                    ->add('notifyEventLevels', null, array('label' => 'Chci dostávat upozornění pouze u soutěží', 'required' => false))
                    ->add('notifyEventDisciplines', null, array('label' => 'Chci dostávat upozornění pouze u disciplín', 'required' => false))
                    ->add('notifyEventCups', null, array(
                        'label' => 'Chci dostávat upozornění pouze u žebříčků',
                        'required' => false,
                        'choices' => $clubConfigurationPool->getEventCups(true),
                    ))
                ->end();
            }

            if ($clubConfigurationPool->getSettings()->getEnableDocumentNotifications()) {
                $formMapper->with('DocumentsNotifications', array('class' => 'col-md-6'))
                    ->add('notifyDocuments', null, array('label' => 'Chci dostávat upozornění na nové dokumenty', 'required' => false))
                    ->add('notifyDocumentCategories', null, array(
                        'label' => 'Kategorie',
                        'required' => false,
                        'choices' => $this->entityManager->getRepository('Ok99PrivateZoneClassificationBundle:Category')->getNotifiableDocumentsCategories($user),
                    ), array(
                        'admin_code' => 'ok99.privatezone.documents_category',
                    ))
                ->end();
            }

        $formMapper->end();

        /*if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $formMapper->with('Roles')
                ->add('realRoles', 'sonata_security_roles', array(
                    'label'    => 'form.label_roles',
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false
                ))
            ->end();
        }*/

        if ($this->id($this->getSubject())) {
            $this->getRequest()->getSession()->set(User::ID_HANDLER, $this->id($this->getSubject()));
        }
    }

    /**
     * @param DatagridMapper $filterMapper
     * @throws \Exception
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $privacyPolicy = $this->clubConfigurationPool->getCurrentPrivacyPolicy();

        $filterMapper
            ->add('firstname')
            ->add('lastname')
            ->add('regnum')
            ->add('licence', 'doctrine_orm_callback', [
                'callback' => function(ProxyQueryInterface $queryBuilder, $alias, $field, $value) {
                    if ($value == null || $value['value'] == null) {
                        return;
                    }

                    $queryBuilder->leftJoin($alias . '.sportLicences', 'usl');
                    $queryBuilder->andWhere('usl.licence = :licence')->setParameter('licence', $value['value']);;
                },
                'operator_type' => 'sonata_type_equal',
            ])
            ->add('groups');

        if ($privacyPolicy) {
            $positiveUserIds = array_map(
                function ($item) { return $item['id']; },
                $this->entityManager->getRepository('Ok99PrivateZoneBundle:UserPrivacyPolicy')
                    ->createQueryBuilder('p')
                    ->select('u.id')
                    ->leftJoin('p.user', 'u')
                    ->where('p.checksum = :checksum')
                    ->setParameter('checksum', $privacyPolicy->getChecksum())
                    ->getQuery()->getScalarResult()
            );

            $filterMapper->add('privacyPolicyAgreed', 'doctrine_orm_callback', [
                'field_type' => 'choice',
                'callback' => function(ProxyQueryInterface $queryBuilder, $alias, $field, $value) use ($positiveUserIds) {
                    if ($value == null || $value['value'] == null) {
                        return;
                    }

                    switch($value['value']) {
                        case 1:
                            $queryBuilder->andWhere($queryBuilder->expr()->in($alias.'.id', $positiveUserIds));
                            break;
                        case 2:
                            $queryBuilder->andWhere($queryBuilder->expr()->notin($alias.'.id', $positiveUserIds));
                            break;
                    }
                },
                'field_options' => array(
                    'choices' => array(
                        1 => 'label_type_yes',
                        2 => 'label_type_no',
                    ),
                ),
                'transform' => true,
                'operator_type' => 'sonata_type_boolean',
            ]);
        }

        $filterMapper->add('enabled');
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
            ->add('enabled', null, array('editable' => false))
        ;

        if ($this->clubConfigurationPool->getCurrentPrivacyPolicy()) {
            $listMapper
                ->add('privacyPolicyAgreed', null, array(
                    'editable' => false,
                    'template' => 'Ok99PrivateZoneUserBundle:UserAdmin:list_is_privacy_policy_agree.html.twig',
                ))
            ;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')/*$this->isGranted('ROLE_ALLOWED_TO_SWITCH')*/) {
            $listMapper
                ->add('impersonating', 'string', array('template' => 'SonataUserBundle:Admin:Field/impersonating.html.twig'))
            ;
        }

        if ($this->isAdmin()) {
            $listMapper->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array('template' => 'Ok99PrivateZoneUserBundle:UserAdmin:list__action_deactivate.html.twig'),
                )
            ));
        }
    }

    /**
     * @param User $object
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postPersist($object)
    {
        if ($this->clubConfigurationPool->getSettings()->getEnableDocumentNotifications()) {
            $categories = $this->entityManager->getRepository('Ok99PrivateZoneClassificationBundle:Category')->getNotifiableDocumentsCategories($object);

            /** @var Category $category */
            foreach ($categories as $category) {
                // added category
                 if (
                     $object->hasDocumentCategoryNotificationEnabled($category) &&
                     !$category->hasNotifyRecipient($object)
                 ) {
                     $category->addNotifyRecipients($object);
                     $this->entityManager->flush();
                 }

                 // removed category
                 if (
                     !$object->hasDocumentCategoryNotificationEnabled($category) &&
                     $category->hasNotifyRecipient($object)
                 ) {
                     $category->removeNotifyRecipients($object);
                     $this->entityManager->flush();
                 }
            }
        }
    }

    /**
     * @param User $object
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postUpdate($object)
    {
        $this->postPersist($object);
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
            case 'DELETE':
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

    /**
     * @return bool
     */
    public function showCreateButton()
    {
        return $this->isAdmin();
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
     * {@inheritdoc}
     */
    public function getFormBuilder()
    {
        $this->formOptions['data_class'] = $this->getClass();

        $options = $this->formOptions;
        $options['validation_groups'] = 'Default';

        $formBuilder = $this->getFormContractor()->getFormBuilder( $this->getUniqid(), $options);

        $this->defineFormBuilder($formBuilder);

        return $formBuilder;
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
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
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

    /**
     * @param User $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        $object->setUsername($object->getRegnum());
        $object->setClubShortcut($this->clubConfigurationPool->getClubShortcut());
    }

    /**
     * @param User $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);

        $em = $this->getModelManager()->getEntityManager($this->getClass());
        $original = $em->getUnitOfWork()->getOriginalEntityData($object);

        if (!$object->isEnabled() && $original['enabled'] === true) {
            $object->setDeenabledManually(true);
        }
        
        if ($object->isEnabled() && $object->isDeenabledManually()) {
            $object->setDeenabledManually(false);
        }
    }

    /**
     * Generates the breadcrumbs array
     *
     * Note: the method will be called by the top admin instance (parent => child)
     *
     * @param string                       $action
     * @param \Knp\Menu\ItemInterface|null $menu
     *
     * @return array|\Knp\Menu\ItemInterface
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
     * @param string $exportName
     */
    public function setExportName($exportName)
    {
        $this->exportName = $exportName;
    }

    /**
     * @inheritdoc
     */
    public function getExportName(){
        if ($this->exportName) {
            return $this->exportName;
        }

        switch($this->exportType) {
            case self::EXPORT_TYPE_COMMON:
                $exportName = 'Clenove_oddilu';
                break;
            case self::EXPORT_TYPE_DIFF:
                $exportName = 'Zmeny_osobnich_udaju';
                break;
        }
        return $exportName;
    }

    protected $exportListTitle;

    /**
     * @param $title
     */
    public function setExportListTitle($title)
    {
        $this->exportListTitle = $title;
    }

    /**
     * @inheritdoc
     */
    public function getExportListTitle()
    {
        if (!$this->exportListTitle) {
            switch($this->exportType) {
                case self::EXPORT_TYPE_COMMON:
                    $this->exportListTitle = 'Členové oddílu';
                    break;
                case self::EXPORT_TYPE_DIFF:
                    $this->exportListTitle = 'Změny osobních údajů';
                    break;
            }
        }
        return $this->exportListTitle;
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
        return [
            'Jméno' => 'firstname',
            'Příjmení' => 'lastname',
            'Reg. číslo' => 'regnum',
            'Licence' => 'sportLicencesDecorated',
            'SportIdent 1' => 'sportident',
            'SportIdent 2' => 'sportident2',
            'SportIdent 3' => 'sportident3',
            'Datum narození' => 'date_of_birth',
            'Email' => 'email',
            'Email rodiče' => 'email_parent',
            'Telefon' => 'phone',
            'Telefon rodiče' => 'phone_parent',
            'Ulice a č.p.' => 'street',
            'Město' => 'city',
            'PSČ' => 'zip',
        ];
    }

    /**
     * @param string $field
     * @return string
     */
    public function getExportXlsxFieldFormat($field)
    {
        switch($field) {
            case 'date_of_birth':
                return \PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX22;
                break;
            default:
                return \PHPExcel_Style_NumberFormat::FORMAT_TEXT;
        }
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
