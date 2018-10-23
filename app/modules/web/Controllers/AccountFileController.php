<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\Html\Html;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\FileGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Account\AccountFileService;
use SP\Services\Account\AccountService;
use SP\Storage\File\FileHandler;
use SP\Util\ErrorUtil;
use SP\Util\FileUtil;
use SP\Util\Util;

/**
 * Class AccountFileController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountFileController extends ControllerBase implements CrudControllerInterface
{
    const EXTENSIONS_VIEW = ['TXT'];

    use JsonTrait, ItemTrait;

    /**
     * @var AccountFileService
     */
    protected $accountFileService;

    /**
     * View action
     *
     * @param $id
     *
     * @return bool
     */
    public function viewAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('El archivo no existe'), SPException::INFO);
            }

            $this->view->addTemplate('file', 'itemshow');

            if (FileUtil::isImage($fileData)) {
                $this->view->assign('data', chunk_split(base64_encode($fileData->getContent())));
                $this->view->assign('fileData', $fileData);
                $this->view->assign('isImage', 1);

                $this->eventDispatcher->notifyEvent('show.accountFile',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Archivo visualizado'))
                            ->addDetail(__u('Archivo'), $fileData->getName()))
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }

            $extension = mb_strtoupper($fileData->getExtension());

            if (in_array($extension, self::EXTENSIONS_VIEW)) {
                $this->view->assign('extension', $extension);
                $this->view->assign('data', htmlentities($fileData->getContent()));

                $this->eventDispatcher->notifyEvent('show.accountFile',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Archivo visualizado'))
                            ->addDetail(__u('Archivo'), $fileData->getName()))
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }

        return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Archivo no soportado para visualizar'));
    }

    /**
     * Download action
     *
     * @param $id
     *
     * @return string
     */
    public function downloadAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            // Set the security toke to its previous value because we can't tell
            // the browser which will be the new security token (not so good...)
            $this->session->setSecurityKey($this->previousSk);

            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('El archivo no existe'), SPException::INFO);
            }

            $this->eventDispatcher->notifyEvent('download.accountFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Archivo descargado'))
                    ->addDetail(__u('Archivo'), $fileData->getName()))
            );

            $response = $this->router->response();
            $response->header('Cache-Control', 'max-age=60, must-revalidate');
            $response->header('Content-length', $fileData->getSize());
            $response->header('Content-type', $fileData->getType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-transfer-encoding', 'binary');

            $extension = mb_strtoupper($fileData->getExtension());

            if ($extension === 'PDF') {
                $response->header('Content-Disposition', 'inline; filename="' . $fileData->getName() . '"');
            } else {
                $response->header('Set-Cookie', 'fileDownload=true; path=/');
                $response->header('Content-Disposition', 'attachment; filename="' . $fileData->getName() . '"');
            }

            $response->body($fileData->getContent());
            $response->send(true);
        } catch (\Exception $e) {
            processException($e);
        }

        return '';
    }

    /**
     * Upload action
     *
     * @param int $accountId
     *
     * @return bool
     */
    public function uploadAction($accountId)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $file = $this->router->request()->files()->get('inFile');

            if ($accountId === 0 || null === $file) {
                throw new SPException(__u('CONSULTA INVÁLIDA'), SPException::ERROR);
            }

            $allowedExts = $this->configData->getFilesAllowedExts();

            if (empty($allowedExts)) {
                throw new SPException(__u('No hay extensiones permitidas'), SPException::ERROR);
            }

            $fileHandler = new FileHandler($file['tmp_name']);

            $fileData = new FileData();
            $fileData->setAccountId($accountId);
            $fileData->setName(Html::sanitize($file['name']));
            $fileData->setSize($file['size']);
            $fileData->setType($file['type']);

            if ($fileData->getName() !== '') {
                // Comprobamos la extensión del archivo
                $fileData->setExtension(mb_strtoupper(pathinfo($fileData->getName(), PATHINFO_EXTENSION)));

                if (!in_array($fileData->getExtension(), $allowedExts, true)) {
                    throw new SPException(
                        __u('Tipo de archivo no soportado'),
                        SPException::ERROR,
                        sprintf(__('Extensión: %s'), $fileData->getExtension())
                    );
                }
            } else {
                throw new SPException(
                    __u('Archivo inválido'),
                    SPException::ERROR,
                    sprintf(__u('Archivo: %s'), $fileData->getName())
                );
            }

            if (!file_exists($file['tmp_name'])) {
                throw new SPException(
                    __u('Error interno al leer el archivo'),
                    SPException::ERROR,
                    sprintf(__u('Máximo tamaño: %s'), Util::getMaxUpload())
                );
            }

            $allowedSize = $this->configData->getFilesAllowedSize();

            if ($fileData->getSize() > ($allowedSize * 1000)) {
                throw new SPException(
                    __u('Tamaño de archivo superado'),
                    SPException::ERROR,
                    sprintf(__u('Máximo tamaño: %d KB'),
                        $fileData->getRoundSize())
                );
            }

            $fileData->setContent($fileHandler->readToString());

            if ($fileData->getContent() === false) {
                throw new SPException(__u('Error interno al leer el archivo'));
            }

            $this->accountFileService->create($fileData);

            $account = $this->dic->get(AccountService::class)
                ->getById($accountId)
                ->getAccountVData();

            $this->eventDispatcher->notifyEvent('upload.accountFile',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Archivo guardado'))
                        ->addDetail(__u('Archivo'), $fileData->getName())
                        ->addDetail(__u('Cuenta'), $account->getName())
                        ->addDetail(__u('Cliente'), $account->getClientName())
                        ->addDetail(__u('Tipo'), $fileData->getType())
                        ->addDetail(__u('Tamaño'), $fileData->getRoundSize() . 'KB')
                )
            );

            return $this->returnJsonResponse(0, __u('Archivo guardado'));
        } catch (SPException $e) {
            processException($e);

            return $this->returnJsonResponse(1, $e->getMessage(), [$e->getHint()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Search action
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws SPException
     */
    public function searchAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_FILE_SEARCH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return $this
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $fileGrid = $this->dic->get(FileGrid::class);

        return $fileGrid->updatePager($fileGrid->getGrid($this->accountFileService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     */
    public function createAction()
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Edit action
     *
     * @param $id
     */
    public function editAction($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Delete action
     *
     * @param $id
     *
     * @return bool
     */
    public function deleteAction($id = null)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if ($id === null) {
                $this->accountFileService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent('delete.accountFile.selection',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Archivos eliminados')))
                );

                return $this->returnJsonResponse(0, __u('Archivos eliminados'));
            }

            $this->eventDispatcher->notifyEvent('delete.accountFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Archivo eliminado'))
                    ->addDetail(__u('Archivo'), $id))
            );

            $this->accountFileService->delete($id);

            return $this->returnJsonResponse(0, __u('Archivo Eliminado'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param int $accountId Account's ID
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function listAction($accountId)
    {
        if (!$this->configData->isFilesEnabled()) {
            echo __('Gestión de archivos deshabilitada');
            return;
        }

        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $this->view->addTemplate('files-list', 'account');

            $this->view->assign('deleteEnabled', $this->request->analyzeInt('del', false));
            $this->view->assign('files', $this->dic->get(AccountFileService::class)->getByAccountId($accountId));
            $this->view->assign('sk', $this->session->getSecurityKey());
            $this->view->assign('fileViewRoute', Acl::getActionRoute(Acl::ACCOUNT_FILE_VIEW));
            $this->view->assign('fileDownloadRoute', Acl::getActionRoute(Acl::ACCOUNT_FILE_DOWNLOAD));
            $this->view->assign('fileDeleteRoute', Acl::getActionRoute(Acl::ACCOUNT_FILE_DELETE));

            if (!is_array($this->view->files) || count($this->view->files) === 0) {
                $this->view->addTemplate('no_records_found', '_partials');

                $this->view->assign('message', __('No hay archivos asociados a la cuenta'));

                $this->view();

                return;
            }

            $this->eventDispatcher->notifyEvent('list.accountFile', new Event($this));
        } catch (\Exception $e) {
            processException($e);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION, true, 'files-list');
        }

        $this->view();
    }

    /**
     * Initialize class
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->accountFileService = $this->dic->get(AccountFileService::class);
    }
}
