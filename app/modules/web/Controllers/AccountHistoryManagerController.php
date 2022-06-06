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
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Account\AccountHistoryServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountHistoryGrid;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Auth\Browser\BrowserAuthInterface;

/**
 * Class AccountHistoryManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountHistoryManagerController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    private AccountHistoryServiceInterface $accountHistoryService;
    private AccountHistoryGrid             $accountHistoryGrid;
    private AccountServiceInterface        $accountService;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        TemplateInterface $template,
        BrowserAuthInterface $browser,
        LayoutHelper $layoutHelper,
        AccountHistoryServiceInterface $accountHistoryService,
        Helpers\Grid\AccountHistoryGrid $accountHistoryGrid,
        AccountServiceInterface $accountService
    ) {
        $this->accountHistoryService = $accountHistoryService;
        $this->accountHistoryGrid = $accountHistoryGrid;
        $this->accountService = $accountService;

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
     * @return bool
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNTMGR_HISTORY_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        return $this->accountHistoryGrid->updatePager(
            $this->accountHistoryGrid->getGrid($this->accountHistoryService->search($itemSearchData)),
            $itemSearchData
        );
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
                $this->accountHistoryService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.accountHistory.selection',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Accounts removed'))
                    )
                );

                return $this->returnJsonResponse(
                    JsonResponse::JSON_SUCCESS,
                    __u('Accounts removed')
                );
            }
            $accountDetails = $this->accountHistoryService->getById($id);

            $this->accountHistoryService->delete($id);

            $this->eventDispatcher->notifyEvent(
                'delete.accountHistory',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account removed'))
                        ->addDetail(__u('Account'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Account removed')
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
     * Saves restore action
     *
     * @param  int  $id  Account's history ID
     *
     * @return bool
     * @throws \JsonException
     */
    public function restoreAction(int $id): bool
    {
        try {
            $accountDetails = $this->accountHistoryService->getById($id);

            if ($accountDetails->isModify) {
                $this->accountService->editRestore($id, $accountDetails->getAccountId());
            } else {
                $this->accountService->createFromHistory($accountDetails);
            }

            $this->eventDispatcher->notifyEvent(
                'restore.accountHistory',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Account restored'))
                        ->addDetail(__u('Account'), $accountDetails->getName())
                        ->addDetail(__u('Client'), $accountDetails->getClientName())
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Account restored')
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
}