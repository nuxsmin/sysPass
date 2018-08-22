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
use SP\DataModel\UserGroupData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGroupGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\UserGroupForm;
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::GROUP_SEARCH)) {
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

        $userGroupGrid = $this->dic->get(UserGroupGrid::class);

        return $userGroupGrid->updatePager($userGroupGrid->getGrid($this->userGroupService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::GROUP_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Grupo'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'userGroup/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.userGroup.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user group's data
     *
     * @param $userGroupId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Repositories\NoSuchItemException
     */
    protected function setViewData($userGroupId = null)
    {
        $this->view->addTemplate('usergroup', 'itemshow');

        $group = $userGroupId ? $this->userGroupService->getById($userGroupId) : new UserGroupData();

        $this->view->assign('group', $group);
        $this->view->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModelSelected($this->userToUserGroupService->getUsersByGroupId($userGroupId)));
        $this->view->assign('usedBy', $this->userGroupService->getUsageByUsers($userGroupId));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
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
        if (!$this->acl->checkUserAccess(Acl::GROUP_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Editar Grupo'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'userGroup/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userGroup.edit', new Event($this));

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
        if (!$this->acl->checkUserAccess(Acl::GROUP_DELETE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            if ($id === null) {
                $this->userGroupService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(Acl::GROUP, $id);

                $this->eventDispatcher->notifyEvent('delete.userGroup.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Grupos eliminados')))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupos eliminados'));
            } else {
                $this->userGroupService->delete($id);

                $this->deleteCustomFieldsForItem(Acl::GROUP, $id);

                $this->eventDispatcher->notifyEvent('delete.userGroup',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Grupo eliminado'))
                        ->addDetail(__u('Grupo'), $id))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupo eliminado'));
            }
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
        if (!$this->acl->checkUserAccess(Acl::GROUP_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new UserGroupForm($this->dic);
            $form->validate(Acl::GROUP_CREATE);

            $groupData = $form->getItemData();

            $id = $this->userGroupService->create($groupData);

            $this->addCustomFieldsForItem(Acl::GROUP, $id, $this->request);

            $this->eventDispatcher->notifyEvent('create.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Grupo creado'))
                    ->addDetail(__u('Nombre'), $groupData->getName()))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupo creado'));
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
        if (!$this->acl->checkUserAccess(Acl::GROUP_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new UserGroupForm($this->dic, $id);
            $form->validate(Acl::GROUP_EDIT);

            $groupData = $form->getItemData();

            $this->userGroupService->update($groupData);

            $this->updateCustomFieldsForItem(Acl::GROUP, $id, $this->request);

            $this->eventDispatcher->notifyEvent('edit.userGroup',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Grupo actualizado'))
                    ->addDetail(__u('Nombre'), $groupData->getName()))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Grupo actualizado'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

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
        if (!$this->acl->checkUserAccess(Acl::GROUP_VIEW)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Ver Grupo'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userGroup', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
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

        $this->userGroupService = $this->dic->get(UserGroupService::class);
        $this->userToUserGroupService = $this->dic->get(UserToUserGroupService::class);
    }
}