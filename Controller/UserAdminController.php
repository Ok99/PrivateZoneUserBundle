<?php

namespace Ok99\PrivateZoneCore\UserBundle\Controller;

use Ok99\PrivateZoneBundle\Controller\SecuredCRUDController;
use Ok99\PrivateZoneBundle\HttpFoundation\AjaxResponse;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserAdminController extends SecuredCRUDController
{
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
     * @return Response
     */
    public function storeCroppedAvatarAction(Request $request)
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
                $user = $this->container->get('security.token_storage')->getToken()->getUser();

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

        if (!in_array($propertyName, ['skinColor','menuSidebarCollapsed','menuSidebarExpandOnHover','controlSidebarLightSkin','suggestEventClasses'])) {
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

                try {
                    $this->getDoctrine()->getManager()->flush($user);
                } catch(\Exception $e) {
                    $response->addError('Storing failed');
                }
            }
        }

        return new Response($response);
    }

    /**
     * {@inheritdoc}
     */
    public function createAction(Request $request = null)
    {
        if (!$this->admin->isAdmin()) {
            return $this->redirect($this->generateUrl('admin_privatezonecore_user_user_list'));
        }

        return parent::createAction($request);
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
}