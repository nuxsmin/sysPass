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
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Notification\UseCases\Dtos\GetNotificationCommand;
use SP\Domain\Notification\UseCases\GetNotificationUseCase;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__;
use function SP\__u;

/**
 * Class CreateController
 */
final class CreateController extends NotificationViewBase
{
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        GetNotificationUseCase $getNotificationUseCase,
        UserService $userService
    ) {
        parent::__construct($application, $webControllerHelper, $getNotificationUseCase, $userService);
    }

    #[Action(ResponseType::JSON)]
    public function createAction(): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::NOTIFICATION_CREATE)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        $this->view->assign('header', __('New Notification'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'notification/saveCreate');

        $this->setViewData();

        $this->eventDispatcher->notify('show.notification.create', new Event($this));

        return ActionResponse::ok('', ['html' => $this->render()]);
    }
}
