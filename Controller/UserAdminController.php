<?php

namespace Ok99\PrivateZoneCore\UserBundle\Controller;

use Exporter\Source\ArraySourceIterator;
use Ok99\PrivateZoneBundle\Controller\SecuredCRUDController;
use Ok99\PrivateZoneBundle\Entity\EventOrganizeEntry;
use Ok99\PrivateZoneBundle\EntityAudit\AuditReader;
use Ok99\PrivateZoneBundle\Export\XlsxWriter;
use Ok99\PrivateZoneBundle\HttpFoundation\AjaxResponse;
use Ok99\PrivateZoneCore\UserBundle\Admin\UserAdmin;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserAdminController extends SecuredCRUDController
{
    /**
     * Create action.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \Twig_Error_Runtime
     * @throws AccessDeniedException If access is not granted
     */
    public function createAction(Request $request = null)
    {
        if (!$this->admin->isAdmin()) {
            return $this->redirect($this->generateUrl('admin_privatezonecore_user_user_list'));
        }

        $request = $this->resolveRequest($request);
        // the key used to lookup the template
        $templateKey = 'edit';

        if (false === $this->admin->isGranted('CREATE')) {
            throw new AccessDeniedException();
        }

        $clubConfigurationPool = $this->container->get('ok99.privatezone.club_configuration_pool');

        $class = new \ReflectionClass($this->admin->hasActiveSubClass() ? $this->admin->getActiveSubClass() : $this->admin->getClass());

        if ($class->isAbstract()) {
            return $this->render(
                'SonataAdminBundle:CRUD:select_subclass.html.twig',
                array(
                    'base_template' => $this->getBaseTemplate(),
                    'admin'         => $this->admin,
                    'action'        => 'create',
                ),
                null,
                $request
            );
        }

        $object = $this->admin->getNewInstance();

        $preResponse = $this->preCreate($request, $object);
        if ($preResponse !== null) {
            return $preResponse;
        }

        $this->admin->setSubject($object);

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->admin->getForm();
        $form->setData($object);
        $form->handleRequest($request);

        // u dema neni vytvoreni uzivatele mozne
        if ($clubConfigurationPool->isDemo()) {
            $this->addFlash(
                'sonata_flash_error',
                $this->admin->trans(
                    'Vytvoření uživatele není v demo verzi možné',
                    [],
                    'Ok99PrivateZoneBundle'
                )
            );
        }

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // u dema neni vytvoreni uzivatele mozne
            if ($clubConfigurationPool->isDemo()) {
                $isFormValid = false;
            }

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode($request) || $this->isPreviewApproved($request))) {
                if (false === $this->admin->isGranted('CREATE', $object)) {
                    throw new AccessDeniedException();
                }

                try {
                    $object = $this->admin->create($object);

                    $postResponse = $this->postCreate($request, $form, $object);
                    if ($postResponse !== null) {
                        return $postResponse;
                    }

                    $this->getDoctrine()->getManager()->flush($object);

                    if ($this->isXmlHttpRequest($request)) {
                        return $this->renderJson(array(
                            'result'   => 'ok',
                            'objectId' => $this->admin->getNormalizedIdentifier($object),
                            'editUrl' => $this->admin->generateObjectUrl('edit', $object),

                        ), 200, array(), $request);
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->admin->trans(
                            'flash_create_success',
                            array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                            'SonataAdminBundle'
                        )
                    );

                    // redirect to edit mode
                    return $this->redirectTo($object, $request);
                } catch (ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                $postResponse = $this->postInvalidCreate($request, $form, $object);
                if ($postResponse !== null) {
                    return $postResponse;
                }

                if (!$this->isXmlHttpRequest($request)) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->admin->trans(
                            'flash_create_error',
                            array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                            'SonataAdminBundle'
                        )
                    );
                } else {
                    return $this->renderJson(array(
                        'result'   => 'error',
                        'errors' => $this->getErrorMessages($form),
                    ), 200, array(), $request);
                }
            } elseif ($this->isPreviewRequested($request)) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $view = $form->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($view, $this->admin->getFormTheme());

        return $this->render($this->admin->getTemplate($templateKey), array(
            'action' => 'create',
            'form'   => $view,
            'object' => $object,
        ), null, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function showAction($id = null, Request $request = null)
    {
        $request = $this->resolveRequest($request);
        $id = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        return $this->redirect($this->generateUrl('admin_privatezonecore_user_user_edit', array('id' => $object->getId())));
    }

    /**
     * {@inheritdoc}
     */
    public function historyAction($id = null, Request $request = null)
    {
        if (!$this->admin->isAdmin()) {
            $request = $this->resolveRequest($request);
            $id = $request->get($this->admin->getIdParameter());
            $object = $this->admin->getObject($id);

            return $this->redirect($this->generateUrl('admin_privatezonecore_user_user_edit', array('id' => $object->getId())));
        }

        return parent::historyAction($id, $request);
    }

    /**
     * Store avatar
     *
     * @param Request $request
     * @param int $userId
     * @return Response
     */
    public function storeCroppedAvatarAction(Request $request, $userId)
    {
        $response = new AjaxResponse();
        $response->setSuccess(false);

        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $documentRoot = $this->container->get('kernel')->getRootDir() . '/../web';

        $crop = $request->request->get('crop');

        $relativePathname = $request->request->get('pathname');
        $pathname = $documentRoot . $relativePathname;

        $avatarFilename = str_replace('tmp_', '', basename($pathname));
        $avatarPathname = str_replace(basename($pathname), $avatarFilename, $pathname);
        $avatarRelativePathname = str_replace(basename($relativePathname), $avatarFilename, $relativePathname);

        if (!file_exists($pathname)) {
            throw new NotFoundHttpException();
        }

        $mimeType = $this->detectMimeType($pathname);

        switch (strtolower($mimeType)) {
            case 'image/jpg':
            case 'image/jpeg':
                $image = imagecreatefromjpeg($pathname);
                break;
            case 'image/png':
                $image = imagecreatefrompng($pathname);
                break;
        }

        $avatarImage = $this->createTrueColorImage($crop['width'], $crop['height'], $mimeType);

        $success = imagecopyresampled(
            $avatarImage,
            $image,
            0,
            0,
            $crop['x'],
            $crop['y'],
            $crop['width'],
            $crop['height'],
            $crop['width'],
            $crop['height']
        );

        if ($success) {
            switch (strtolower($mimeType)) {
                case 'image/jpg':
                case 'image/jpeg':
                    $success = imagejpeg($avatarImage, $avatarPathname, 90);
                    break;
                case 'image/png':
                    $success = imagepng($avatarImage, $avatarPathname);
                    break;
            }

            if ($success) {
                @chown($avatarPathname, posix_getuid());

                // remove source tmp image
                @unlink($pathname);

                /** @var User $user */
                if (!$this->admin->isAdmin()) {
                    $user = $this->container->get('security.token_storage')->getToken()->getUser();
                } else {
                    $user = $this->get('doctrine.orm.entity_manager')->getRepository('Ok99PrivateZoneUserBundle:User')->find($userId);
                }

                // remove all old avatars
                if ($user->getAvatar() && file_exists($documentRoot . $user->getAvatar())) {
                    @unlink($documentRoot . $user->getAvatar());
                }
                foreach (glob(dirname($pathname) . sprintf('/%s_*', $user->getRegnum())) as $file) {
                    if (is_file($file) && strtolower(basename($file)) != strtolower($avatarFilename)) {
                        @unlink($file);
                    }
                }

                // remove all avatar thumbnails
                foreach(['t','tr'] as $baseDir) {
                    $baseDirPath = sprintf('%s/%s', $documentRoot, $baseDir);
                    $iterator = new \DirectoryIterator($baseDirPath);
                    foreach ($iterator as $node) {
                        if ($node->isDir() && !$node->isDot()) {
                            $path = sprintf('%s%s/%s_*', $node->getPathname(), dirname($avatarRelativePathname), $user->getRegnum());
                            foreach (glob($path) as $file) {
                                if (is_file($file) && strtolower(basename($file)) != strtolower($avatarFilename)) {
                                    @unlink($file);
                                }
                            }
                        }
                    }
                }

                $user->setAvatar($avatarRelativePathname);

                try {
                    $this->getDoctrine()->getManager()->flush($user);
                    $response->setSuccess(true);
                    $response->setData([
                        'pathname' => $avatarRelativePathname
                    ]);
                } catch(\Exception $e) {
                }
            }
        }

        return new Response($response);
    }

    /**
     * Store user property
     *
     * @param Request $request
     * @return Response
     */
    public function storePropertyAction(Request $request)
    {
        $response = new AjaxResponse();

        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $propertyName = $request->request->get('name');
        $propertyValue = $request->request->get('value');

        if (!$propertyName) {
            $response->addError('Property is not set');
        }

        if (!in_array($propertyName, [
            'skinColor',
            'menuSidebarCollapsed',
            'menuSidebarExpandOnHover',
            'controlSidebarLightSkin',
            'suggestEventClasses',
            'notifyEventEntryDates',
            'notifyDocuments',
        ])) {
            $response->addError('Access denied');
        }

        if ($response->isSuccess()) {
            /** @var User $user */
            $user = $this->container->get('security.token_storage')->getToken()->getUser();

            if (!method_exists($user, 'set'.ucfirst($propertyName))) {
                $response->addError('Property doesn\'t exists');
            }

            if ($response->isSuccess() && $user->{'get'.ucfirst($propertyName)}() != $propertyValue) {
                $user->{'set'.ucfirst($propertyName)}($propertyValue);

                switch($propertyName) {
                    case 'notifyDocuments':
                        // add all notifiable categories if empty
                        if (((bool)$propertyValue) && $user->getNotifyDocumentCategories()->count() == 0) {
                            $notifiableCategories = $this->getDoctrine()
                                ->getRepository('Ok99PrivateZoneClassificationBundle:Category')
                                ->getNotifiableDocumentsCategories($user);

                            if ($notifiableCategories) {
                                foreach($notifiableCategories as $notifiableCategory) {
                                    $user->addNotifyDocumentCategories($notifiableCategory);
                                    $notifiableCategory->addNotifyRecipients($user);
                                }
                            }
                        }
                        break;
                }

                try {
                    $this->getDoctrine()->getManager()->flush();
                } catch(\Exception $e) {
                    $response->addError('Storing failed');
                }
            }
        }

        return new Response($response);
    }

    protected function solveResponseIfNotObjectOwner($id, Request $request, $object, $action)
    {
        if (!$this->admin->isAdmin($object) && !$this->isUserValid($object)) {
            return $this->redirect($this->generateUrl('admin_privatezonecore_user_user_list'));
        }

        return $this->doAction($action, $id, $request);
    }

    protected function solveResponseIfNotValidRequest($id, Request $request, $object, $action)
    {
        if (!$this->admin->isAdmin($object) && !$this->isUserValid($object)) {
            return $this->redirect($this->generateUrl('admin_privatezonecore_user_user_list'));
        }

        return $this->doAction($action, $id, $request);
    }

    protected function isUserValid($object)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        return $object && $user->getId() == $object->getId();
    }

    /**
     * @param string $pathname
     * @return string
     */
    protected function detectMimeType($pathname)
    {
        if (function_exists('finfo_file')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($pathname);
        } else {
            $mimeType = mime_content_type($pathname);
        }

        return $mimeType;
    }

    /**
     * @param integer $width
     * @param integer $height
     * @return resource
     */
    protected function createTrueColorImage($width, $height, $mimeType)
    {
        $image = imagecreatetruecolor($width, $height);

        switch ($mimeType) {
            case 'image/png':
                imagecolortransparent($image, imagecolorallocatealpha($image, 0, 0, 0, 127));
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            default:
                imagealphablending($image, true);
        }

        return $image;
    }

    /**
     * {@inheritdoc}
     */
    public function listAction(Request $request = null)
    {
        $entityManager = $this->getDoctrine();

        $yearFrom = $entityManager->getRepository('Ok99PrivateZoneUserBundle:User')
                ->createQueryBuilder('u')
                ->select('YEAR(u.createdAt) as year')
                ->orderBy('u.createdAt', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult() + 1;

        $yearTo = (new \DateTime())->format('Y');

        $years = $yearFrom < $yearTo ? range($yearFrom, $yearTo) : [];
        rsort($years);

        $this->setResponseParameter('years', $years);

        return parent::listAction($request);
    }

    /**
     * @param Request|null $request
     * @return Response
     */
    public function exportAction(Request $request = null)
    {
        $this->admin->setExportType(UserAdmin::EXPORT_TYPE_COMMON);

        return parent::exportAction($request);
    }

    /**
     * @param Request|null $request
     * @param int $year
     * @return Response
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \SimpleThings\EntityAudit\AuditException
     */
    public function yearDiffAction(Request $request, $year)
    {
        $this->admin->setExportType(UserAdmin::EXPORT_TYPE_DIFF);
        $this->admin->setExportName(sprintf('Zmeny_osobnich_udaju_%s', $year));
        $this->admin->setExportListTitle(sprintf('Změny os. údajů za rok %s', $year));

        if (false === $this->admin->isGranted('EXPORT')) {
            throw new AccessDeniedException();
        }

        /*** FETCH DATA ***/
        $reader = new AuditReader(
            $this->get('doctrine.orm.entity_manager'),
            $this->get('simplethings_entityaudit.config')
        );

        $exportFields = $this->admin->getExportFields();
        unset($exportFields['Licence']);

        $intersectFields = array_map(function(){ return ''; }, array_flip(array_values($exportFields)));

        $usersData = [];
        $usersDataRevisionsDiff = [];

        /** @var User $user */
        foreach($this->get('doctrine.orm.entity_manager')->getRepository('Ok99PrivateZoneUserBundle:User')->getActiveUsers() as $user) {
            $currentRev = $reader->getLastRevision($this->admin->getClass(), $user->getId(), $year);
            $userDataRevisionsDiff = [];

            if ($currentRev) {
                $userData = array_intersect_key($reader->find($this->admin->getClass(), $user->getId(), $currentRev)->toArray(), $intersectFields);

                $prevRev = $reader->getLastRevision($this->admin->getClass(), $user->getId(), $year - 1);
                if (!$prevRev) {
                    foreach (range($year - 2, 2016) as $_year) {
                        $prevRev = $reader->getLastRevision($this->admin->getClass(), $user->getId(), $_year);
                        if ($prevRev) break;
                    }
                }

                if ($prevRev) {
                    $diff = $reader->diff($this->admin->getClass(), $user->getId(), $prevRev, $currentRev);
                    $diff = array_filter($diff, function ($field) { return $field['old'] !== $field['new']; });
                    $diff = array_intersect_key($diff, $intersectFields);

                    if ($diff) {
                        $userDataRevisionsDiff = array_map(function ($field) { return $field['new']; }, $diff);
                    }
                }
            } else {
                $userData = array_intersect_key($user->toArray(), $intersectFields);
            }

            $usersData[$user->getId()] = array_merge($intersectFields, $userData); // sort by keys at first
            $usersDataRevisionsDiff[$user->getId()] = $userDataRevisionsDiff;
        }

        /*** STORE DATA ***/
        $writer = new XlsxWriter($this->admin, $this->container->get('phpexcel'), 'php://output', true, $exportFields);

        return new StreamedResponse(function() use ($writer, $usersData, $usersDataRevisionsDiff) {
            $writer->open();
            $row = 1;

            foreach ($usersData as $userId => $userData) {
                $row++;
                $column = 0;

                foreach($userData as $field => $value) {
                    $writer->getObjPHPExcel()->getActiveSheet()->setCellValueByColumnAndRow($column, $row, $this->admin->exportFieldRender($field, $value));
                    $writer->getObjPHPExcel()->getActiveSheet()->getStyleByColumnAndRow($column, $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    $writer->getObjPHPExcel()->getActiveSheet()->getStyleByColumnAndRow($column, $row)->getNumberFormat()->setFormatCode($this->admin->getExportXlsxFieldFormat($field));

                    if (isset($usersDataRevisionsDiff[$userId][$field])) {
                        $writer->getObjPHPExcel()->getActiveSheet()->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
                        $writer->getObjPHPExcel()->getActiveSheet()->getStyleByColumnAndRow($column, $row)->applyFromArray(array('fill' => array('type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'E3B9B8'))));
                    }

                    $column++;
                }
            }

            $writer->close();
        }, 200, array(
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s.xlsx"', $this->admin->getExportName()),
        ));
    }
}
