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
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\UserGroupData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGroupGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\UserGroupForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Repositories\NoSuchItemException;
use SP\Services\Auth\AuthException;
use SP\Services\ServiceException;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserGroup\UserToUserGroupService;

/**
 * Class GroupController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UserGroupController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var UserGroupService
     */
    protected $userGroupService;
    /**
     * @var UserToUserGroupService
     */
    protected $userToUserGroupService;

    /**
     * Search action
     *
     * @return bool
     * @throws ConstraintException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws QueryException
     * @throws SPException
     */
    public function searchAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::GROUP_SEARCH)) {
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

        $userGroupGrid = $this->dic->get(UserGroupGrid::class);

        return $userGroupGrid->updatePager($userGroupGrid->getGrid($this->userGroupService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     */
    public function createAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::GROUP_CREATE)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            $this->view->assign('header', __('New Group'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'userGroup/saveCreate');

            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.userGroup.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user group's data
     *
     * @param $userGroupId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    protected function setViewData($userGroupId = null)
    {
        $this->view->addTemplate('user_group', 'itemshow');

        $userGroupData = $userGroupId ? $this->userGroupService->getById($userGroupId) : new UserGroupData();

        $this->view->assign('group', $userGroupData);

        $users = $userGroupData->getUsers() ?: [];

        $this->view->assign('users',
            SelectItemAdapter::factory(UserService::getItemsBasic())
                ->getItemsFromModelSelected($users));
        $this->view->assign('usedBy', $this->userGroupService->getUsageByUsers($userGroupId));

        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(Acl::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign('customFields', $this->getCustomFieldsForItem(Acl::GROUP, $userGroupId));
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
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::GROUP_EDIT)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            $this->view->assign('header', __('Edit Group'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'userGroup/saveEdit/' . $id);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userGroup.edit', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

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
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::GROUP_DELETE)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            if ($id === null) {
                $this->userGroupService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.userGroup.selection',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Groups deleted')))
                );

                $this->deleteCustomFieldsForItem(Acl::GROUP, $id);

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Groups deleted'));
            } else {
                $this->userGroupService->delete($id);

                $this->eventDispatcher->notifyEvent('delete.userGroup',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Group deleted'))
                        ->addDetail(__u('Group'), $id)
                        ->addExtra('userGroupId', $id))
                );

                $this->deleteCustomFieldsForItem(Acl::GROUP, $id);

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Group deleted'));
            }
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
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::GROUP_CREATE)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            $form = new UserGroupForm($this->dic);
            $form->validate(Acl::GROUP_CREATE);

            $groupData = $form->getItemData();

            $id = $this->userGroupService->create($groupData);

            $this->eventDispatcher->notifyEvent('create.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Group added'))
                    ->addDetail(__u('Name'), $groupData->getName()))
            );

            $this->addCustomFieldsForItem(Acl::GROUP, $id, $this->request);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Group added'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

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
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::GROUP_EDIT)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            $form = new UserGroupForm($this->dic, $id);
            $form->validate(Acl::GROUP_EDIT);

            $groupData = $form->getItemData();

            $this->userGroupService->update($groupData);

            $this->eventDispatcher->notifyEvent('edit.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Group updated'))
                    ->addDetail(__u('Name'), $groupData->getName())
                    ->addExtra('userGroupId', $id))
            );

            $this->updateCustomFieldsForItem(Acl::GROUP, $id, $this->request);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Group updated'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

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

            if (!$this->acl->checkUserAccess(Acl::GROUP_VIEW)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            $this->view->assign('header', __('View Group'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userGroup', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
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

        $this->userGroupService = $this->dic->get(UserGroupService::class);
    }
}