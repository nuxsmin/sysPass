<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Notification;

use SP\Core\Application;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Notification\Models\Notification;
use SP\Domain\Notification\UseCases\Dtos\GetNotificationCommand;
use SP\Domain\Notification\UseCases\GetNotificationUseCase;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;

/**
 * Class NotificationViewBase
 */
abstract class NotificationViewBase extends ControllerBase
{
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        private readonly GetNotificationUseCase $getNotificationUseCase,
        private readonly UserService $userService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    protected function setViewData(?int $notificationId = null): void
    {
        $this->view->addTemplate('notification');

        $notification = $notificationId
            ? $this->getNotificationUseCase->execute(new GetNotificationCommand($notificationId))->notification
            : new Notification();

        $this->view->assign('notification', $notification);

        if ($this->userDto->isAdminApp) {
            $this->view->assign(
                'users',
                SelectItemAdapter::factory($this->userService->getAll())
                    ->getItemsFromModelSelected([$notification->userId])
            );
        }

        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::NOTIFICATION));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
