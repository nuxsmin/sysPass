<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
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
use SP\Services\Auth\AuthException;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\ErrorUtil;
use SP\Util\FileUtil;

/**
 * Class AccountFileController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountFileController extends ControllerBase implements CrudControllerInterface
{
    const MIME_VIEW = ['text/plain'];

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
                throw new SPException(__u('File does not exist'), SPException::INFO);
            }

            $this->view->addTemplate('file', 'itemshow');

            if (FileUtil::isImage($fileData)) {
                $this->view->assign('data', chunk_split(base64_encode($fileData->getContent())));
                $this->view->assign('fileData', $fileData);
                $this->view->assign('isImage', 1);

                $this->eventDispatcher->notifyEvent('show.accountFile',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileData->getName()))
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }

            $type = strtolower($fileData->getType());

            if (in_array($type, self::MIME_VIEW)) {
                $this->view->assign('mime', $type);
                $this->view->assign('data', htmlentities($fileData->getContent()));

                $this->eventDispatcher->notifyEvent('show.accountFile',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileData->getName()))
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('File not supported for preview'));
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

            // Set the security token to its previous value because we can't tell
            // the browser which will be the new security token (not so good...)
            $this->session->setSecurityKey($this->previousSk);

            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('File does not exist'), SPException::INFO);
            }

            $this->eventDispatcher->notifyEvent('download.accountFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('File downloaded'))
                    ->addDetail(__u('File'), $fileData->getName()))
            );

            $response = $this->router->response();
            $response->header('Cache-Control', 'max-age=60, must-revalidate');
            $response->header('Content-length', $fileData->getSize());
            $response->header('Content-type', $fileData->getType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-transfer-encoding', 'binary');

            $type = strtolower($fileData->getType());

            if ($type === 'application/pdf') {
                $response->header('Content-Disposition', 'inline; filename="' . $fileData->getName() . '"');
            } else {
                $response->header('Set-Cookie', 'fileDownload=true; path=/');
                $response->header('Content-Disposition', 'attachment; filename="' . $fileData->getName() . '"');
            }

            $response->body($fileData->getContent());
            $response->send(true);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
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
                throw new SPException(__u('INVALID QUERY'), SPException::ERROR);
            }

            $filesAllowedMime = $this->configData->getFilesAllowedMime();

            if (empty($filesAllowedMime)) {
                throw new SPException(__u('There aren\'t any allowed MIME types'));
            }

            try {
                $fileHandler = new FileHandler($file['tmp_name']);

                $fileData = new FileData();
                $fileData->setAccountId($accountId);
                $fileData->setName(Html::sanitize($file['name']));
                $fileData->setSize($file['size']);
                $fileData->setType($file['type']);
                $fileData->setExtension(mb_strtoupper(pathinfo($fileData->getName(), PATHINFO_EXTENSION)));

                if ($fileData->getName() === '') {
                    throw new SPException(
                        __u('Invalid file'),
                        SPException::ERROR,
                        sprintf(__u('File: %s'), $fileData->getName())
                    );
                }

                $fileHandler->checkFileExists();

                $fileData->setType($this->checkAllowedMimeType($fileData, $fileHandler));

                $allowedSize = $this->configData->getFilesAllowedSize();

                if ($fileData->getSize() > ($allowedSize * 1000)) {
                    throw new SPException(
                        __u('File size exceeded'),
                        SPException::ERROR,
                        sprintf(__u('Maximum size: %d KB'), $fileData->getRoundSize())
                    );
                }

                $fileData->setContent($fileHandler->readToString());
            } catch (FileException $e) {
                throw new SPException(__u('Internal error while reading the file'));
            }

            $this->accountFileService->create($fileData);

            $account = $this->dic->get(AccountService::class)
                ->getById($accountId)
                ->getAccountVData();

            $this->eventDispatcher->notifyEvent('upload.accountFile',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('File saved'))
                        ->addDetail(__u('File'), $fileData->getName())
                        ->addDetail(__u('Account'), $account->getName())
                        ->addDetail(__u('Client'), $account->getClientName())
                        ->addDetail(__u('Type'), $fileData->getType())
                        ->addDetail(__u('Size'), $fileData->getRoundSize() . 'KB')
                )
            );

            return $this->returnJsonResponse(0, __u('File saved'));
        } catch (SPException $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponse(1, $e->getMessage(), [$e->getHint()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param FileData    $fileData
     *
     * @param FileHandler $fileHandler
     *
     * @return string
     * @throws SPException
     * @throws FileException
     */
    private function checkAllowedMimeType(FileData $fileData, FileHandler $fileHandler)
    {
        if (in_array($fileData->getType(), $this->configData->getFilesAllowedMime())) {
            return $fileData->getType();
        } elseif (in_array($fileHandler->getFileType(), $this->configData->getFilesAllowedMime())) {
            return $fileHandler->getFileType();
        }

        throw new SPException(
            __u('File type not allowed'),
            SPException::ERROR,
            sprintf(__('MIME type: %s'), $fileData->getType())
        );
    }

    /**
     * Search action
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function searchAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_FILE_SEARCH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
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
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
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
        throw new RuntimeException('Not implemented');
    }

    /**
     * Edit action
     *
     * @param $id
     */
    public function editAction($id)
    {
        throw new RuntimeException('Not implemented');
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
                        ->addDescription(__u('Files deleted')))
                );

                return $this->returnJsonResponse(0, __u('Files deleted'));
            }

            $this->eventDispatcher->notifyEvent('delete.accountFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('File deleted'))
                    ->addDetail(__u('File'), $id))
            );

            $this->accountFileService->delete($id);

            return $this->returnJsonResponse(0, __u('File Deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param int $accountId Account's ID
     *
     * @throws ContainerExceptionInterface
     */
    public function listAction($accountId)
    {
        if (!$this->configData->isFilesEnabled()) {
            echo __('Files management disabled');
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

                $this->view->assign('message', __('There are no linked files for the account'));

                $this->view();

                return;
            }

            $this->eventDispatcher->notifyEvent('list.accountFile', new Event($this));
        } catch (Exception $e) {
            processException($e);

            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_EXCEPTION, true, 'files-list');
        }

        $this->view();
    }

    /**
     * Initialize class
     *
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->accountFileService = $this->dic->get(AccountFileService::class);
    }
}
