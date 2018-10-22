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
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountForm;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountBulkRequest;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\Account\AccountSearchService;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Tag\TagService;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Util\Util;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AccountManagerController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    /**
     * @var AccountService
     */
    protected $accountService;
    /**
     * @var AccountSearchService
     */
    protected $accountSearchService;

    /**
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function searchAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::ACCOUNTMGR_SEARCH)) {
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
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $accountGrid = $this->dic->get(AccountGrid::class);

        $filter = new AccountSearchFilter();
        $filter->setTxtSearch($itemSearchData->getSeachString());
        $filter->setLimitCount($itemSearchData->getLimitCount());
        $filter->setLimitStart($itemSearchData->getLimitStart());
        $filter->setStringFilters($this->accountSearchService->analyzeQueryFilters($itemSearchData->getSeachString()));

        return $accountGrid->updatePager(
            $accountGrid->getGrid(
                $this->accountService->getByFilter($filter)),
            $itemSearchData);
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
                $this->accountService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(Acl::ACCOUNT, $id);

                $this->eventDispatcher->notifyEvent('delete.account.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Cuentas eliminadas')))
                );

                return $this->returnJsonResponseData(JsonResponse::JSON_SUCCESS, __u('Cuentas eliminadas'));
            }

            $accountDetails = $this->accountService->getById($id)->getAccountVData();

            $this->accountService->delete($id);

            $this->deleteCustomFieldsForItem(Acl::ACCOUNT, $id);

            $this->eventDispatcher->notifyEvent('delete.account',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cuenta eliminada'))
                    ->addDetail(__u('Cuenta'), $accountDetails->getName())
                    ->addDetail(__u('Cliente'), $accountDetails->getClientName()))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Cuenta eliminada'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * saveBulkEditAction
     *
     * @return bool
     */
    public function saveBulkEditAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $form = new AccountForm($this->dic);
            $form->validate(Acl::ACCOUNTMGR_BULK_EDIT);

            $request = new AccountBulkRequest(
                Util::itemsIdAdapter($this->request->analyzeString('itemsId')),
                $form->getItemData());
            $request->setDeleteHistory($this->request->analyzeBool('delete_history', false));

            if ($request->isDeleteHistory()) {
                $accountHistoryService = $this->dic->get(AccountHistoryService::class);
                $accountHistoryService->deleteByAccountIdBatch($request->getItemsId());
            }

            $this->accountService->updateBulk($request);

//            $this->updateCustomFieldsForItem(Acl::ACCOUNT, $id, $this->request);

            $this->eventDispatcher->notifyEvent('edit.account.bulk',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Cuentas actualizadas')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Cuentas actualizadas'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * bulkEditAction
     *
     * @return bool
     */
    public function bulkEditAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::ACCOUNTMGR)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
            }

            $this->view->assign('header', __('Actualización Masiva'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'accountManager/saveBulkEdit');
            $this->view->assign('itemsId', $this->getItemsIdFromRequest($this->request));

            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.account.bulkEdit', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data
     */
    protected function setViewData()
    {
        $this->view->addTemplate('account_bulkedit', 'itemshow');

        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ITEMS_MANAGE));

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

    /**
     * Initialize class
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->accountService = $this->dic->get(AccountService::class);
        $this->accountSearchService = $this->dic->get(AccountSearchService::class);
    }
}