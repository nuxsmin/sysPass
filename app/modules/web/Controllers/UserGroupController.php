<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\UserGroupData;
use SP\Forms\UserGroupForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserGroup\UserToUserGroupService;

/**
 * Class GroupController
 *
 * @package SP\Modules\Web\Controllers
 */
class UserGroupController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_SEARCH)) {
            return;
        }

        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $grid = $itemsGridHelper->getUserGroupsGrid($this->userGroupService->search($this->getSearchData($this->configData)))->updatePager();

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyze('activetab', 0));
        $this->view->assign('data', $grid);

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Grupo'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'userGroup/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.userGroup.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $userGroupId
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function setViewData($userGroupId = null)
    {
        $this->view->addTemplate('usergroup', 'itemshow');

        $group = $userGroupId ? $this->userGroupService->getById($userGroupId) : new UserGroupData();

        $this->view->assign('group', $group);
        $this->view->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModelSelected($this->userToUserGroupService->getUsersByGroupId($userGroupId)));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::GROUP, $userGroupId));
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Grupo'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'userGroup/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userGroup.edit', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Delete action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->userGroupService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::GROUP, $id);

                $this->eventDispatcher->notifyEvent('delete.userGroup.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Grupos eliminados')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupos eliminados'));
            } else {
                $this->userGroupService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::GROUP, $id);

                $this->eventDispatcher->notifyEvent('delete.userGroup',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Grupo eliminado'))
                        ->addDetail(__u('Grupo'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupo eliminado'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_CREATE)) {
            return;
        }

        try {
            $form = new UserGroupForm();
            $form->validate(ActionsInterface::GROUP_CREATE);

            $groupData = $form->getItemData();

            $id = $this->userGroupService->create($groupData, $groupData->getUsers());

            $this->addCustomFieldsForItem(ActionsInterface::GROUP, $id);

            $this->eventDispatcher->notifyEvent('create.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Grupo creado'))
                    ->addDetail(__u('Nombre'), $groupData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupo creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_EDIT)) {
            return;
        }

        try {
            $form = new UserGroupForm($id);
            $form->validate(ActionsInterface::GROUP_EDIT);

            $groupData = $form->getItemData();

            $this->userGroupService->update($groupData);

            $this->updateCustomFieldsForItem(ActionsInterface::GROUP, $id);

            $this->eventDispatcher->notifyEvent('edit.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Grupo actualizado'))
                    ->addDetail(__u('Nombre'), $groupData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupo actualizado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * View action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::GROUP_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Grupo'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userGroup', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
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

        $this->userGroupService = $this->dic->get(UserGroupService::class);
        $this->userToUserGroupService = $this->dic->get(UserToUserGroupService::class);
    }
}