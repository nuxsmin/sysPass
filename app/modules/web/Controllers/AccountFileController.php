<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\Html\Html;
use SP\Http\Request;
use SP\Mgmt\Files\FileUtil;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Account\AccountFileService;
use SP\Util\ErrorUtil;
use SP\Util\Util;

/**
 * Class AccountFileController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccountFileController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * @var AccountFileService
     */
    protected $accountFileService;

    /**
     * View action
     *
     * @param $id
     */
    public function viewAction($id)
    {
        try {
            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(SPException::SP_INFO, __u('El archivo no existe'));
            }

            $this->view->addTemplate('file', 'itemshow');

            if (FileUtil::isImage($fileData)) {
                $this->view->assign('data', chunk_split(base64_encode($fileData->getContent())));
                $this->view->assign('fileData', $fileData);
                $this->view->assign('isImage', 1);

                $this->eventDispatcher->notifyEvent('show.accountFile', $this);

                $this->returnJsonResponseData(['html' => $this->render()]);
            }

            if (mb_strtoupper($fileData->getExtension()) === 'TXT') {
                $this->view->assign('data', htmlentities($fileData->getContent()));

                $this->eventDispatcher->notifyEvent('show.accountFile', $this);

                $this->returnJsonResponseData(['html' => $this->render()]);
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(1, __('Archivo no soportado para visualizar'));
    }

    /**
     * Download action
     *
     * @param $id
     */
    public function downloadAction($id)
    {
        try {
            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(SPException::SP_INFO, __u('El archivo no existe'));
            }

            // Enviamos el archivo al navegador
            header('Set-Cookie: fileDownload=true; path=/');
            header('Cache-Control: max-age=60, must-revalidate');
            header('Content-length: ' . $fileData->getSize());
            header('Content-type: ' . $fileData->getType());
            header('Content-Disposition: attachment; filename="' . $fileData->getName() . '"');
            header('Content-Description: PHP Generated Data');
            header('Content-transfer-encoding: binary');

            $this->eventDispatcher->notifyEvent('download.accountFile', $this);

            exit($fileData->getContent());
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);
        }
    }

    /**
     * Upload action
     *
     * @param int $accountId
     */
    public function uploadAction($accountId)
    {
        try {
            $file = $this->router->request()->files()->get('inFile');

            if ($accountId === 0 || null === $file) {
                throw new SPException(SPException::SP_ERROR, __u('CONSULTA INVÁLIDA'));
            }

            $allowedExts = $this->configData->getFilesAllowedExts();

            if (count($allowedExts) === 0) {
                throw new SPException(SPException::SP_ERROR, __u('No hay extensiones permitidas'));
            }

            $fileData = new FileData();
            $fileData->setAccountId($accountId);
            $fileData->setName(Html::sanitize($file['name']));
            $fileData->setSize($file['size']);
            $fileData->setType($file['type']);

            if ($fileData->getName() !== '') {
                // Comprobamos la extensión del archivo
                $fileData->setExtension(mb_strtoupper(pathinfo($fileData->getName(), PATHINFO_EXTENSION)));

                if (!in_array($fileData->getExtension(), $allowedExts, true)) {
                    throw new SPException(SPException::SP_ERROR, __u('Tipo de archivo no soportado'), sprintf(__('Extensión: %s'), $fileData->getExtension()));
                }
            } else {
                throw new SPException(SPException::SP_ERROR, __u('Archivo inválido'), sprintf(__u('Archivo: %s'), $fileData->getName()));
            }

            if (!file_exists($file['tmp_name'])) {
                throw new SPException(SPException::SP_ERROR, __u('Error interno al leer el archivo'), sprintf(__u('Máximo tamaño: %s'), Util::getMaxUpload()));
            }

            $allowedSize = $this->configData->getFilesAllowedSize();

            if ($fileData->getSize() > ($allowedSize * 1000)) {
                throw new SPException(SPException::SP_ERROR, __u('Tamaño de archivo superado'), sprintf(__u('Máximo tamaño: %d KB'), $fileData->getRoundSize()));
            }

            // Leemos el archivo a una variable
            $fileData->setContent(file_get_contents($file['tmp_name']));

            if ($fileData->getContent() === false) {
                throw new SPException(SPException::SP_ERROR, __u('Error interno al leer el archivo'));
            }

            $this->accountFileService->create($fileData);

            $this->eventDispatcher->notifyEvent('upload.accountFile', $this);

            $this->returnJsonResponse(0, __u('Archivo guardado'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage(), [$e->getHint()]);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Search action
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNT_FILE_SEARCH)) {
            return;
        }

        $itemsGridHelper = new ItemsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $grid = $itemsGridHelper->getFilesGrid($this->accountFileService->search($this->getSearchData($this->configData)))->updatePager();

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyze('activetab', 0));
        $this->view->assign('data', $grid);

        $this->returnJsonResponseData(['html' => $this->render()]);
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
     */
    public function deleteAction($id)
    {
        try {
            $this->accountFileService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.accountFile', $this);

            $this->returnJsonResponse(0, __('Archivo Eliminado'));
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
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
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function listAction($accountId)
    {
        if (!$this->configData->isFilesEnabled()) {
            die(__('Gestión de archivos deshabilitada'));
        }

        try {
            $this->setAction(ActionsInterface::ACCOUNT_FILE);

            $this->view->addTemplate('files-list', 'account');

            $this->view->assign('deleteEnabled', Request::analyze('del', 0));
            $this->view->assign('files', (new AccountFileService())->getByAccountId($accountId));
            $this->view->assign('sk', $this->session->getSecurityKey());
            $this->view->assign('fileViewRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_VIEW));
            $this->view->assign('fileDownloadRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DOWNLOAD));
            $this->view->assign('fileDeleteRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DELETE));

            if (!is_array($this->view->files) || count($this->view->files) === 0) {
                return;
            }

            $this->eventDispatcher->notifyEvent('list.accountFile', $this);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), true);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION);
        }

        $this->view();
    }

    /**
     * Initialize class
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->accountFileService = new AccountFileService();
    }
}