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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\UserData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\UserForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Auth\AuthException;
use SP\Services\Mail\MailService;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserPassRecover\UserPassRecoverService;
use SP\Services\UserProfile\UserProfileService;

/**
 * Class UserController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UserController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    protected ?UserService $userService = null;

    /**
     * Search action
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign(
            'index',
            $this->request->analyzeInt('activetab', 0)
        );
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $userGrid = $this->dic->get(UserGrid::class);

        return $userGrid->updatePager(
            $userGrid->getGrid($this->userService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function createAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_CREATE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('New User'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'user/saveCreate');

            $this->setViewData();

            $this->eventDispatcher->notifyEvent(
                'show.user.create',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
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
     * Sets view data for displaying user's data
     *
     * @param int|null $userId
     *
     * @throws SPException
     * @throws ContainerExceptionInterface
     */
    protected function setViewData(?int $userId = null): void
    {
        $this->view->addTemplate('user', 'itemshow');

        $user = $userId
            ? $this->userService->getById($userId)
            : new UserData();

        $this->view->assign('user', $user);
        $this->view->assign(
            'groups',
            SelectItemAdapter::factory(UserGroupService::getItemsBasic())
                ->getItemsFromModel()
        );
        $this->view->assign(
            'profiles',
            SelectItemAdapter::factory(UserProfileService::getItemsBasic())
                ->getItemsFromModel()
        );
        $this->view->assign(
            'isUseSSO',
            $this->configData->isAuthBasicAutoLoginEnabled()
        );
        $this->view->assign(
            'mailEnabled',
            $this->configData->isMailEnabled()
        );
        $this->view->assign(
            'nextAction',
            Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE)
        );

        if ($this->view->isView === true
            || ($this->configData->isDemoEnabled()
                && $user->getLogin() === 'demo')
        ) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');

            $this->view->assign(
                'usage',
                array_map(
                    static function ($value) {
                        switch ($value->ref) {
                            case 'Account':
                                $value->icon = 'description';
                                break;
                            case 'UserGroup':
                                $value->icon = 'group';
                                break;
                            case 'PublicLink':
                                $value->icon = 'link';
                                break;
                            default:
                                $value->icon = 'info_outline';
                        }

                        return $value;
                    },
                    $this->userService->getUsageForUser($userId)
                )
            );
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign(
            'showViewCustomPass',
            $this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW_PASS)
        );
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(ActionsInterface::USER, $userId)
        );
    }

    /**
     * Edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function editAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('Edit User'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'user/saveEdit/' . $id);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.user.edit',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
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
     * Edit user's pass action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function editPassAction(int $id): bool
    {
        try {
            // Comprobar si el usuario a modificar es distinto al de la sesión
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT_PASS, $id)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->addTemplate('user_pass', 'itemshow');

            $this->view->assign('header', __('Password Change'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'user/saveEditPass/' . $id);

            $user = $id
                ? $this->userService->getById($id)
                : new UserData();

            $this->view->assign('user', $user);

            $this->eventDispatcher->notifyEvent(
                'show.user.editPass',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
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
     * Delete action
     *
     * @param int|null $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_DELETE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->userService
                    ->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.user.selection',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Users deleted'))
                            ->setExtra('userId', $this->getItemsIdFromRequest($this->request))
                    )
                );

                $this->deleteCustomFieldsForItem(
                    ActionsInterface::USER,
                    $id
                );

                return $this->returnJsonResponse(
                    JsonResponse::JSON_SUCCESS,
                    __u('Users deleted')
                );
            }

            $this->userService->delete($id);

            $this->deleteCustomFieldsForItem(
                ActionsInterface::USER,
                $id
            );

            $this->eventDispatcher->notifyEvent(
                'delete.user',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('User deleted'))
                        ->addDetail(__u('User'), $id)
                        ->addExtra('userId', $id)
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('User deleted')
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
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function saveCreateAction()
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_CREATE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new UserForm($this->dic);
            $form->validate(Acl::USER_CREATE);

            $itemData = $form->getItemData();

            $id = $this->userService->create($itemData);

            $this->eventDispatcher->notifyEvent(
                'create.user',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('User added'))
                        ->addDetail(__u('User'), $itemData->getName())
                )
            );

            $this->addCustomFieldsForItem(
                ActionsInterface::USER,
                $id,
                $this->request
            );

            $this->checkChangeUserPass($id, $itemData);

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('User added')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
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
     * @param int      $userId
     * @param UserData $userData
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     */
    protected function checkChangeUserPass(int $userId, UserData $userData): void
    {
        if ($userData->isChangePass()) {
            $hash = $this->dic->get(UserPassRecoverService::class)
                ->requestForUserId($userId);

            $this->dic->get(MailService::class)
                ->send(
                    __('Password Change'),
                    $userData->getEmail(),
                    UserPassRecoverService::getMailMessage($hash)
                );
        }
    }

    /**
     * Saves edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function saveEditAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new UserForm($this->dic, $id);
            $form->validate(ActionsInterface::USER_EDIT);

            $itemData = $form->getItemData();

            $this->userService->update($itemData);

            $this->eventDispatcher->notifyEvent(
                'edit.user',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('User updated'))
                        ->addDetail(__u('User'), $itemData->getName())
                        ->addExtra('userId', $id)
                )
            );

            $this->updateCustomFieldsForItem(
                ActionsInterface::USER,
                $id,
                $this->request
            );

            $this->checkChangeUserPass($id, $itemData);

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('User updated')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
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
     * Saves edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function saveEditPassAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_EDIT_PASS, $id)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new UserForm($this->dic, $id);
            $form->validate(ActionsInterface::USER_EDIT_PASS);

            $itemData = $form->getItemData();

            $this->userService->updatePass($id, $itemData->getPass());

            $this->eventDispatcher->notifyEvent(
                'edit.user.pass',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Password updated'))
                        ->addDetail(__u('User'), $id)
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Password updated')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
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
     * View action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::USER_VIEW)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('View User'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.user',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
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
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checkLoggedIn();

        $this->userService = $this->dic->get(UserService::class);
    }
}