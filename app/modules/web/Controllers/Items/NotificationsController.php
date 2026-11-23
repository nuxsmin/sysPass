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

namespace SP\Modules\Web\Controllers\Items;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Providers\Html;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Mvc\Controller\SimpleControllerHelper;

use function SP\__;

/**
 * Class NotificationsController
 */
final class NotificationsController extends SimpleControllerBase
{
    public function __construct(
        SimpleControllerHelper $simpleControllerHelper,
        private readonly NotificationService $notificationService
    ) {
        parent::__construct($simpleControllerHelper);

        $this->checks();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function notificationsAction(): ActionResponse
    {
        $notifications = array_map(
            static function ($notification) {
                /** @var \SP\Domain\Notification\Models\Notification $notification */
                return sprintf(
                    '(%s) - %s',
                    $notification->getComponent(),
                    Html::truncate(Html::stripTags($notification->getDescription()), 30)
                );
            },
            $this->notificationService->getAllActiveForCurrentUser()
        );

        $count = count($notifications);

        return ActionResponse::ok('', [
            'message'       => __('There aren\'t any pending notifications'),
            'message_has'   => sprintf(__('There are pending notifications: %d'), $count),
            'count'         => $count,
            'notifications' => $notifications,
            'hash'          => sha1(implode('', $notifications)),
        ]);
    }
}
