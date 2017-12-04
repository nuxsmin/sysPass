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

use SP\Auth\AuthUtil;
use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionUtil;
use SP\DataModel\UserData;
use SP\Forms\UserForm;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\User\UserService;

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
     */
    public function searchAction()
    {

    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Usuario'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'user/saveCreate');

        try {
            $this->setViewData();
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(0, '', ['html' => $this->render()]);
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $userId
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function setViewData($userId = null)
    {
        $this->view->addTemplate('users', 'itemshow');

        $user = $userId ? $this->userService->getById($userId) : new UserData();

        $this->view->assign('user', $user);
        $this->view->assign('groups', $this->getUserGroups());
        $this->view->assign('profiles', $this->getUserProfiles());
        $this->view->assign('isUseSSO', $this->configData->isAuthBasicAutoLoginEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true || $user->getUserLogin() === 'demo') {
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
        $this->view->assign('header', __('Editar Usuario'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'user/saveEdit/' . $id);

        try {
            $this->setViewData($id);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(0, '', ['html' => $this->render()]);
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
        if ($id !== $this->userData->getUserId() && !$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('userspass', 'itemshow');

        $this->view->assign('header', __('Cambio de Clave'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'user/saveEditPass/' . $id);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        try {
            $user = $id ? $this->userService->getById($id) : new UserData();

            $this->view->assign('user', $user);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(0, '', ['html' => $this->render()]);
    }

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id)
    {
        try {
            $userService = new UserService();
            $userService->logAction($id, ActionsInterface::USER_DELETE);
            $userService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('delete.user', $this);

            $this->returnJsonResponse(0, __('Usuario eliminado'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        try {
            $form = new UserForm();
            $form->validate(ActionsInterface::USER_CREATE);

            $userService = new UserService();

            $id = $userService->create($form->getItemData());
            $userService->logAction($id, ActionsInterface::USER_CREATE);

            $this->addCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('edit.user', $this);

            if ($form->getItemData()->isUserIsChangePass()
                && !AuthUtil::mailPassRecover($form->getItemData())
            ) {
                $this->returnJsonResponse(2, __('Usuario creado'), __('No se pudo realizar la petición de cambio de clave.'));
            }

            $this->returnJsonResponse(0, __('Usuario creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        try {
            $isLdap = Request::analyze('isLdap', 0);

            $form = new UserForm($id);
            $form->setIsLdap($isLdap);
            $form->validate(ActionsInterface::USER_EDIT);

            if ($isLdap) {
                // FIXME: LDAP Service
                $userService = new UserService();
            } else {
                $userService = new UserService();
            }

            $userService->update($form->getItemData());
            $userService->logAction($id, ActionsInterface::USER_EDIT);

            $this->updateCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('edit.user', $this);

            $this->returnJsonResponse(0, __('Usuario actualizado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditPassAction($id)
    {
        try {
            $form = new UserForm($id);
            $form->validate(ActionsInterface::USER_EDIT_PASS);

            $userService = new UserService();
            $userService->updatePass($form->getItemData());
            $userService->logAction($id, ActionsInterface::USER_EDIT_PASS);

            $this->eventDispatcher->notifyEvent('editPass.user', $this);

            $this->returnJsonResponse(0, __('Clave actualizada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(1, $e->getMessage());
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
        $this->view->assign('header', __('Ver Usuario'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(0, '', ['html' => $this->render()]);
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