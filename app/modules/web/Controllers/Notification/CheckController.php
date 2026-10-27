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

use Exception;
use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Notification\UseCases\CheckNotificationUseCase;
use SP\Domain\Notification\UseCases\Dtos\CheckNotificationCommand;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class CheckController
 */
final class CheckController extends NotificationSaveBase
{
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        private readonly CheckNotificationUseCase $checkNotificationUseCase
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    #[Action(ResponseType::JSON)]
    public function checkAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::NOTIFICATION_CHECK)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->checkNotificationUseCase->execute(new CheckNotificationCommand($id));
        } catch (Exception $e) {
            return ActionResponse::error($e->getMessage());
        }

        return ActionResponse::ok(__u('Notification read'));
    }
}
