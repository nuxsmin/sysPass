<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
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
use SP\Providers\Mail\Mailer;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserPassRecover\UserPassRecoverService;
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyze('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return $this
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getSearchGrid()
    {
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount());

        return $itemsGridHelper->updatePager($itemsGridHelper->getUsersGrid($this->userService->search($itemSearchData)), $itemSearchData);
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

            $this->eventDispatcher->notifyEvent('show.user.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
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
        $this->view->assign('groups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $this->view->assign('profiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());
        $this->view->assign('isUseSSO', $this->configData->isAuthBasicAutoLoginEnabled());
        $this->view->assign('sk', $this->session->generateSecurityKey());
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

            $this->eventDispatcher->notifyEvent('show.user.edit', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
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

            $this->eventDispatcher->notifyEvent('show.user.editPass', new Event($this));

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
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_DELETE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);

        try {
            if ($id === null) {
                $this->userService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::USER, $id);

                $this->eventDispatcher->notifyEvent('delete.user.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Usuarios eliminados')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuarios eliminados'));
            } else {
                $this->userService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::USER, $id);

                $this->eventDispatcher->notifyEvent('delete.user',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Usuario eliminado'))
                        ->addDetail(__u('Usuario'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuario eliminado'));
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
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_CREATE)) {
            return;
        }

        try {
            $form = new UserForm();
            $form->validate(ActionsInterface::USER_CREATE);

            $itemData = $form->getItemData();

            $id = $this->userService->create($itemData);

            $this->addCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('create.user',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Usuario creado'))
                    ->addDetail(__u('Usuario'), $itemData->getName()))
            );

            $this->checkChangeUserPass($itemData);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuario creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param UserData $userData
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Providers\Mail\MailerException
     * @throws \SP\Services\ServiceException
     */
    protected function checkChangeUserPass(UserData $userData)
    {
        if ($userData->isChangePass()) {
            $hash = $this->dic->get(UserPassRecoverService::class)->requestForUserId($userData->getId());
            $this->dic->get(Mailer::class)->send(__('Cambio de Clave'), $userData->getEmail(), UserPassRecoverService::getMailMessage($hash));

//            $this->returnJsonResponse(
//                JsonResponse::JSON_WARNING,
//                __u('Usuario creado'),
//                [__('No se pudo realizar la petición de cambio de clave.')]
//            );
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
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT)) {
            return;
        }

        try {
            $form = new UserForm($id);
            $form->setIsLdap(Request::analyze('isLdap', 0));
            $form->validate(ActionsInterface::USER_EDIT);

            $itemData = $form->getItemData();

            $this->userService->update($itemData);

            $this->updateCustomFieldsForItem(ActionsInterface::USER, $id);

            $this->eventDispatcher->notifyEvent('edit.user',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Usuario actualizado'))
                    ->addDetail(__u('Usuario'), $itemData->getName()))
            );

            $this->checkChangeUserPass($itemData);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Usuario actualizado'));
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
     */
    public function saveEditPassAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT_PASS)) {
            return;
        }

        try {
            $form = new UserForm($id);
            $form->validate(ActionsInterface::USER_EDIT_PASS);

            $itemData = $form->getItemData();

            $this->userService->updatePass($id, $itemData->getPass());

            $this->eventDispatcher->notifyEvent('edit.user.pass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Clave actualizada'))
                    ->addDetail(__u('Usuario'), $id))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Clave actualizada'));
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
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_VIEW)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Ver Usuario'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.user', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->userService = $this->dic->get(UserService::class);
    }
}