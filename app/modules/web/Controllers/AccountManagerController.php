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
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Account\AccountHistoryServiceInterface;
use SP\Domain\Account\AccountSearchServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\Account\Services\AccountBulkRequest;
use SP\Domain\Account\Services\AccountSearchFilter;
use SP\Domain\Category\Services\CategoryService;
use SP\Domain\Client\Services\ClientService;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Domain\Tag\Services\TagService;
use SP\Domain\User\Services\UserGroupService;
use SP\Domain\User\Services\UserService;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountGrid;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Auth\Browser\BrowserAuthInterface;
use SP\Util\Util;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountManagerController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    private AccountServiceInterface        $accountService;
    private AccountSearchServiceInterface  $accountSearchService;
    private AccountHistoryServiceInterface $accountHistoryService;
    private AccountGrid                    $accountGrid;
    private CustomFieldServiceInterface    $customFieldService;

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
        AccountServiceInterface $accountService,
        AccountSearchServiceInterface $accountSearchService,
        AccountHistoryServiceInterface $accountHistoryService,
        Helpers\Grid\AccountGrid $accountGrid,
        CustomFieldServiceInterface $customFieldService
    ) {
        $this->accountService = $accountService;
        $this->accountSearchService = $accountSearchService;
        $this->accountHistoryService = $accountHistoryService;
        $this->accountGrid = $accountGrid;
        $this->customFieldService = $customFieldService;

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
     * @throws \SP\Core\Exceptions\SPException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNTMGR_SEARCH)) {
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
     * @return \SP\Html\DataGrid\DataGridInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $filter = new AccountSearchFilter();
        $filter->setLimitCount($itemSearchData->getLimitCount());
        $filter->setLimitStart($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $filter->setStringFilters(
                $this->accountSearchService->analyzeQueryFilters($itemSearchData->getSeachString())
            );
            $filter->setCleanTxtSearch($this->accountSearchService->getCleanString());
        }

        return $this->accountGrid->updatePager(
            $this->accountGrid->getGrid(
                $this->accountService->getByFilter($filter)
            ),
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
                $this->accountService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(ActionsInterface::ACCOUNT, $id, $this->customFieldService);

                $this->eventDispatcher->notifyEvent(
                    'delete.account.selection',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Accounts removed'))
                    )
                );

                return $this->returnJsonResponseData(
                    JsonResponse::JSON_SUCCESS,
                    __u('Accounts removed')
                );
            }

            $accountDetails = $this->accountService
                ->getById($id)
                ->getAccountVData();

            $this->accountService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::ACCOUNT, $id, $this->customFieldService);

            $this->eventDispatcher->notifyEvent(
                'delete.account',
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

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * saveBulkEditAction
     *
     * @return bool
     * @throws \JsonException
     */
    public function saveBulkEditAction(): bool
    {
        try {
            $form = new AccountForm($this->dic);
            $form->validate(ActionsInterface::ACCOUNTMGR_BULK_EDIT);

            $request = new AccountBulkRequest(
                Util::itemsIdAdapter($this->request->analyzeString('itemsId')),
                $form->getItemData()
            );
            $request->setDeleteHistory($this->request->analyzeBool('delete_history', false));

            if ($request->isDeleteHistory()) {
                $this->accountHistoryService->deleteByAccountIdBatch($request->getItemsId());
            }

            $this->accountService->updateBulk($request);

            $this->eventDispatcher->notifyEvent(
                'edit.account.bulk',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Accounts updated'))
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Accounts updated')
            );
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * bulkEditAction
     *
     * @return bool
     * @throws \JsonException
     */
    public function bulkEditAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNTMGR)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('Bulk Update'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'accountManager/saveBulkEdit');
            $this->view->assign('itemsId', $this->getItemsIdFromRequest($this->request));

            $this->setViewData();

            $this->eventDispatcher->notifyEvent(
                'show.account.bulkEdit',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data
     */
    protected function setViewData(): void
    {
        $this->view->addTemplate('account_bulkedit', 'itemshow');

        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE));

        // FIXME: Use IoC
        $clients = SelectItemAdapter::factory(ClientService::getItemsBasic())->getItemsFromModel();
        $categories = SelectItemAdapter::factory(CategoryService::getItemsBasic())->getItemsFromModel();
        $tags = SelectItemAdapter::factory(TagService::getItemsBasic())->getItemsFromModel();
        $users = SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModel();
        $userGroups = SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel();

        $this->view->assign('users', $users);
        $this->view->assign('userGroups', $userGroups);

        $this->view->assign('clients', $clients);
        $this->view->assign('categories', $categories);
        $this->view->assign('tags', $tags);

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}