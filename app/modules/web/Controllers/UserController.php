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

use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionUtil;
use SP\DataModel\UserData;
use SP\Forms\UserForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Providers\Auth\AuthUtil;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;

/**
 * Class UserController
 *
 * @package SP\Modules\Web\Controllers
 */
class UserController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * Search action
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_SEARCH)) {
            return;
        }

        $itemsGridHelper = new ItemsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $grid = $itemsGridHelper->getUsersGrid($this->userService->search($this->getSearchData($this->configData)))->updatePager();

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
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Usuario'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'user/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.user.create', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $userId
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($userId = null)
    {
        $this->view->addTemplate('user', 'itemshow');

        $user = $userId ? $this->userService->getById($userId) : new UserData();

        $this->view->assign('user', $user);
        $this->view->assign('groups', (new SelectItemAdapter(UserGroupService::getItemsBasic()))->getItemsFromModel());
        $this->view->assign('profiles', (new SelectItemAdapter(UserProfileService::getItemsBasic()))->getItemsFromModel());
        $this->view->assign('isUseSSO', $this->configData->isAuthBasicAutoLoginEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true || $user->getLogin() === 'demo') {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::USER, $userId));
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Editar Usuario'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'user/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.user.edit', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Edit user's pass action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editPassAction($id)
    {
        // Comprobar si el usuario a modificar es distinto al de la sesión
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT_PASS, $this->userData->getId())) {
            return;
        }

        $this->view->addTemplate('userpass', 'itemshow');

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Cambio de Clave'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'user/saveEditPass/' . $id);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        try {
            $user = $id ? $this->userService->getById($id) : new UserData();

            $this->view->assign('user', $user);

            $this->eventDispatcher->notifyEvent('show.user.editPass', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_DELETE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);

        try {
//            $this->userService->logAction($id, ActionsInterface::USER_DELETE);
            $this->userService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('delete.user', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuario eliminado'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_CREATE)) {
            return;
        }

        try {
            $form = new UserForm();
            $form->validate(ActionsInterface::USER_CREATE);

            $id = $this->userService->create($form->getItemData());
//            $this->userService->logAction($id, ActionsInterface::USER_CREATE);

            $this->addCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('create.user', $this);

            if ($form->getItemData()->isIsChangePass()
                && !AuthUtil::mailPassRecover($form->getItemData())
            ) {
                $this->returnJsonResponse(
                    JsonResponse::JSON_WARNING,
                    __u('Usuario creado'),
                    [__('No se pudo realizar la petición de cambio de clave.')]
                );
            }

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuario creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT)) {
            return;
        }

        try {
            $form = new UserForm($id);
            $form->setIsLdap(Request::analyze('isLdap', 0));
            $form->validate(ActionsInterface::USER_EDIT);

            $this->userService->update($form->getItemData());
//            $this->userService->logAction($id, ActionsInterface::USER_EDIT);

            $this->updateCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('edit.user', $this);

            if ($form->getItemData()->isIsChangePass()
                && !AuthUtil::mailPassRecover($form->getItemData())
            ) {
                $this->returnJsonResponse(
                    JsonResponse::JSON_WARNING,
                    __u('Usuario actualizado'),
                    [__('No se pudo realizar la petición de cambio de clave.')]
                );
            }

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuario actualizado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditPassAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT_PASS)) {
            return;
        }

        try {
            $form = new UserForm($id);
            $form->validate(ActionsInterface::USER_EDIT_PASS);

            $this->userService->updatePass($form->getItemData());
//            $this->userService->logAction($id, ActionsInterface::USER_EDIT_PASS);

            $this->eventDispatcher->notifyEvent('edit.user.pass', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Clave actualizada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_VIEW)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Ver Usuario'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.user', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->userService = new UserService();
    }
}