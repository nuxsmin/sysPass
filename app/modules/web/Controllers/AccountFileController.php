<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use Exception;
use Klein\Klein;
use RuntimeException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\DataModel\FileData;
use SP\Domain\Account\AccountFileServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\Html;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Modules\Web\Controllers\Helpers\Grid\FileGrid;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Auth\Browser\BrowserAuthInterface;
use SP\Util\ErrorUtil;
use SP\Util\FileUtil;

/**
 * Class AccountFileController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountFileController extends ControllerBase implements CrudControllerInterface
{
    private const MIME_VIEW = ['text/plain'];

    use JsonTrait, ItemTrait;

    private AccountFileServiceInterface $accountFileService;
    private AccountServiceInterface     $accountService;
    private FileGrid                    $fileGrid;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        TemplateInterface $template,
        BrowserAuthInterface $browser,
        LayoutHelper $layoutHelper
    ) {
        parent::__construct(
            $application,
            $theme,
            $router,
            $acl,
            $request,
            $extensionChecker,
            $template,
            $browser,
            $layoutHelper
        );

        $this->checkLoggedIn();
    }


    /**
     * View action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('File does not exist'), SPException::INFO);
            }

            $this->view->addTemplate('file', 'itemshow');

            if (FileUtil::isImage($fileData)) {
                $this->view->assign('data', chunk_split(base64_encode($fileData->getContent())));
                $this->view->assign('fileData', $fileData);
                $this->view->assign('isImage', 1);

                $this->eventDispatcher->notifyEvent(
                    'show.accountFile',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileData->getName())
                    )
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }

            $type = strtolower($fileData->getType());

            if (in_array($type, self::MIME_VIEW)) {
                $this->view->assign('mime', $type);
                $this->view->assign('data', htmlentities($fileData->getContent()));

                $this->eventDispatcher->notifyEvent(
                    'show.accountFile',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileData->getName())
                    )
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }

        return $this->returnJsonResponse(
            JsonResponse::JSON_WARNING,
            __u('File not supported for preview')
        );
    }

    /**
     * Download action
     *
     * @param  int  $id
     *
     * @return string
     */
    public function downloadAction(int $id): string
    {
        try {
            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('File does not exist'), SPException::INFO);
            }

            $this->eventDispatcher->notifyEvent(
                'download.accountFile',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('File downloaded'))
                    ->addDetail(__u('File'), $fileData->getName())
                )
            );

            $response = $this->router->response();
            $response->header('Content-Length', $fileData->getSize());
            $response->header('Content-Type', $fileData->getType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-Transfer-Encoding', 'binary');
            $response->header('Accept-Ranges', 'bytes');

            $type = strtolower($fileData->getType());

            if ($type === 'application/pdf') {
                $disposition = sprintf(
                    'inline; filename="%s"',
                    $fileData->getName()
                );
            } else {
                $disposition = sprintf(
                    'attachment; filename="%s"',
                    $fileData->getName()
                );
                $response->header('Set-Cookie', 'fileDownload=true; path=/');
            }

            $response->header('Content-Disposition', $disposition);

            $response->body($fileData->getContent());
            $response->send(true);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );
        }

        return '';
    }

    /**
     * Upload action
     *
     * @param  int  $accountId
     *
     * @return bool
     * @throws \JsonException
     */
    public function uploadAction(int $accountId): bool
    {
        try {
            $file = $this->router->request()->files()->get('inFile');

            if ($accountId === 0 || null === $file) {
                throw new SPException(__u('INVALID QUERY'), SPException::ERROR);
            }

            $filesAllowedMime = $this->configData->getFilesAllowedMime();

            if (count($filesAllowedMime) === 0) {
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

            $account = $this->accountService->getById($accountId)->getAccountVData();

            $this->eventDispatcher->notifyEvent(
                'upload.accountFile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('File saved'))
                        ->addDetail(__u('File'), $fileData->getName())
                        ->addDetail(__u('Account'), $account->getName())
                        ->addDetail(__u('Client'), $account->getClientName())
                        ->addDetail(__u('Type'), $fileData->getType())
                        ->addDetail(__u('Size'), $fileData->getRoundSize().'KB')
                )
            );

            return $this->returnJsonResponse(0, __u('File saved'));
        } catch (SPException $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponse(
                1,
                $e->getMessage(),
                [$e->getHint()]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param  FileData  $fileData
     * @param  FileHandlerInterface  $fileHandler
     *
     * @return string
     * @throws SPException
     * @throws FileException
     */
    private function checkAllowedMimeType(FileData $fileData, FileHandlerInterface $fileHandler): string
    {
        if (in_array($fileData->getType(), $this->configData->getFilesAllowedMime(), true)) {
            return $fileData->getType();
        }

        if (in_array($fileHandler->getFileType(), $this->configData->getFilesAllowedMime(), true)) {
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
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNT_FILE_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign(
            'index',
            $this->request->analyzeInt('activetab', 0)
        );
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return \SP\Html\DataGrid\DataGridInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        return $this->fileGrid->updatePager(
            $this->fileGrid->getGrid($this->accountFileService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * Create action
     */
    public function createAction(): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Edit action
     *
     * @param $id
     */
    public function editAction($id): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Delete action
     *
     * @param  int|null  $id
     *
     * @return bool
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if ($id === null) {
                $this->accountFileService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.accountFile.selection',
                    new Event(
                        $this,
                        EventMessage::factory()->addDescription(__u('Files deleted'))
                    )
                );

                return $this->returnJsonResponse(0, __u('Files deleted'));
            }

            $this->eventDispatcher->notifyEvent(
                'delete.accountFile',
                new Event(
                    $this,
                    EventMessage::factory()->addDescription(__u('File deleted'))->addDetail(__u('File'), $id)
                )
            );

            $this->accountFileService->delete($id);

            return $this->returnJsonResponse(0, __u('File Deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction(): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param  int  $accountId  Account's ID
     */
    public function listAction(int $accountId): void
    {
        if (!$this->configData->isFilesEnabled()) {
            echo __('Files management disabled');

            return;
        }

        try {
            $this->view->addTemplate('files-list', 'account');

            $this->view->assign('deleteEnabled', $this->request->analyzeInt('del', false));
            $this->view->assign('files', $this->accountFileService->getByAccountId($accountId));
            $this->view->assign('fileViewRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_VIEW));
            $this->view->assign('fileDownloadRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DOWNLOAD));
            $this->view->assign('fileDeleteRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DELETE));

            if (!is_array($this->view->files)
                || count($this->view->files) === 0) {
                $this->view->addTemplate(
                    'no_records_found',
                    '_partials'
                );

                $this->view->assign(
                    'message',
                    __('There are no linked files for the account')
                );

                $this->view();

                return;
            }

            $this->eventDispatcher->notifyEvent(
                'list.accountFile',
                new Event($this)
            );
        } catch (Exception $e) {
            processException($e);

            ErrorUtil::showErrorInView(
                $this->view,
                ErrorUtil::ERR_EXCEPTION,
                true,
                'files-list'
            );
        }

        $this->view();
    }
}
