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
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\AccountDefaultPermissionData;
use SP\DataModel\AccountPermission;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountDefaultPermissionGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AccountDefaultPermissionForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountDefaultPermissionService;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;

/**
 * Class AccountDefaultPermissionController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccountDefaultPermissionController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var AccountDefaultPermissionService
     */
    protected $accountDefaultPermissionService;

    /**
     * View action
     *
     * @param $id
     *
     * @return bool
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_VIEW)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Ver Permiso'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.accountDefaultPermission', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying permissions' data
     *
     * @param $permissionId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    protected function setViewData($permissionId = null)
    {
        $this->view->addTemplate('account_default_permission', 'itemshow');

        $accountDefaultPermissionData = $permissionId ? $this->accountDefaultPermissionService->getById($permissionId) : new AccountDefaultPermissionData();
        $accountPermission = $accountDefaultPermissionData->getAccountPermission() ?: new AccountPermission();

        $this->view->assign('permission', $accountDefaultPermissionData);

        $users = SelectItemAdapter::factory(UserService::getItemsBasic());

        $this->view->assign('users', $users->getItemsFromModelSelected([$accountDefaultPermissionData->getUserId()]));
        $this->view->assign('usersView', $users->getItemsFromModelSelected($accountPermission->getUsersView()));
        $this->view->assign('usersEdit', $users->getItemsFromModelSelected($accountPermission->getUsersEdit()));

        $userGroups = SelectItemAdapter::factory(UserGroupService::getItemsBasic());

        $this->view->assign('userGroups', $userGroups->getItemsFromModelSelected([$accountDefaultPermissionData->getUserGroupId()]));
        $this->view->assign('userGroupsView', $userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsView()));
        $this->view->assign('userGroupsEdit', $userGroups->getItemsFromModelSelected($accountPermission->getUserGroupsEdit()));

        $this->view->assign('userProfiles', SelectItemAdapter::factory(UserGroupService::getItemsBasic())
            ->getItemsFromModelSelected([$accountDefaultPermissionData->getUserProfileId()]));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }
    }

    /**
     * Search action
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_SEARCH)) {
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $grid = $this->dic->get(AccountDefaultPermissionGrid::class);

        return $grid->updatePager(
            $grid->getGrid($this->accountDefaultPermissionService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * Create action
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Permiso'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'accountDefaultPermission/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.accountDefaultPermission.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Edit action
     *
     * @param $id
     *
     * @return bool
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Editar Permiso'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'accountDefaultPermission/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.accountDefaultPermission.edit', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
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
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_DELETE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            if ($id === null) {
                $this->accountDefaultPermissionService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent('delete.accountDefaultPermission',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Permisos eliminados')))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Permisos eliminados'));
            }

            $this->accountDefaultPermissionService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.accountDefaultPermission',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Permiso eliminado'))
                        ->addDetail(__u('ID'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Permiso eliminado'));
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
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new AccountDefaultPermissionForm($this->dic);
            $form->validate(Acl::ACCOUNT_DEFAULT_PERMISSION_CREATE);

            $id = $this->accountDefaultPermissionService->create($form->getItemData());

            $this->eventDispatcher->notifyEvent('create.accountDefaultPermission',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Permiso creado'))
                        ->addDetail(__u('ID'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Permiso creado'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     *
     * @return bool
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::ACCOUNT_DEFAULT_PERMISSION_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new AccountDefaultPermissionForm($this->dic, $id);
            $form->validate(Acl::ACCOUNT_DEFAULT_PERMISSION_EDIT);

            $this->accountDefaultPermissionService->update($form->getItemData());

            $this->eventDispatcher->notifyEvent('edit.accountDefaultPermission',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Permiso actualizado'))
                        ->addDetail(__u('ID'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Permiso actualizado'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
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

        $this->accountDefaultPermissionService = $this->dic->get(AccountDefaultPermissionService::class);
    }
}