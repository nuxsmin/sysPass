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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\NotificationData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\NotificationForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Notification\NotificationService;
use SP\Services\User\UserService;

/**
 * Class NotificationController
 *
 * @package SP\Modules\Web\Controllers
 */
class NotificationController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * indexAction
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function indexAction()
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION)) {
            return;
        }

        $this->view->addTemplate('index');

        $this->view->assign('data', $this->getSearchGrid());

        $this->view();
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
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        return $itemsGridHelper->updatePager($itemsGridHelper->getNotificationsGrid($this->notificationService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * View action
     *
     * @param $id
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Notificación'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.notification', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying notification's data
     *
     * @param $notificationId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function setViewData($notificationId = null)
    {
        $this->view->addTemplate('notification');

        $notification = $notificationId ? $this->notificationService->getById($notificationId) : new NotificationData();

        $this->view->assign('notification', $notification);

        if ($this->userData->getIsAdminApp()) {
            $this->view->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModelSelected([$notification->userId]));
        }

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::NOTIFICATION));

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
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Create action
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nueva Notificación'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'notification/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.notification.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Edit action
     *
     * @param $id
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_EDIT)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Editar Notificación'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'notification/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.notification.edit', new Event($this));

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
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                if ($this->userData->getIsAdminApp()) {
                    $this->notificationService->deleteAdminBatch($this->getItemsIdFromRequest($this->request));
                } else {
                    $this->notificationService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));
                }

                $this->eventDispatcher->notifyEvent('delete.notification.selection',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Notificaciones eliminadas')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Notificaciones eliminadas'));
            } else {
                if ($this->userData->getIsAdminApp()) {
                    $this->notificationService->deleteAdmin($id);
                } else {
                    $this->notificationService->delete($id);
                }

                $this->eventDispatcher->notifyEvent('delete.notification',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Notificación eliminada'))
                            ->addDetail(__u('Notificación'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Notificación eliminada'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Check action
     *
     * @param $id
     */
    public function checkAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_CHECK)) {
            return;
        }

        try {
            $this->notificationService->setCheckedById($id);

            $this->eventDispatcher->notifyEvent('check.notification',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Notificación leída'))
                        ->addDetail(__u('Notificación'), $id))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Notificación leída'));
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
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_CREATE)) {
            return;
        }

        try {
            $form = new NotificationForm();
            $form->validate(Acl::NOTIFICATION_CREATE);

            $this->notificationService->create($form->getItemData());

            $this->eventDispatcher->notifyEvent('create.notification',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Notificación creada')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Notificación creada'));
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
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::NOTIFICATION_EDIT)) {
            return;
        }

        try {
            $form = new NotificationForm($id);
            $form->validate(Acl::NOTIFICATION_EDIT);

            $this->notificationService->update($form->getItemData());

            $this->eventDispatcher->notifyEvent('edit.notification',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Notificación actualizada')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Notificación actualizada'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->notificationService = $this->dic->get(NotificationService::class);
    }
}