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

use SP\Core\Application;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Html\Html;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Http\Services\JsonResponse;
use SP\Domain\Notification\Ports\NotificationService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class NotificationsController
 */
final class NotificationsController extends SimpleControllerBase
{
    private NotificationService $notificationService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        NotificationService $notificationService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->checks();

        $this->notificationService = $notificationService;
    }


    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function notificationsAction(): void
    {
        $notifications = array_map(
            static function ($notification) {
                /** @@var $notification \SP\Domain\Notification\Models\Notification */
                return sprintf(
                    '(%s) - %s',
                    $notification->getComponent(),
                    Html::truncate(Html::stripTags($notification->getDescription()), 30)
                );
            },
            $this->notificationService->getAllActiveForCurrentUser()
        );

        $count = count($notifications);

        $jsonResponse = new JsonMessage();
        $jsonResponse->setStatus(0);
        $jsonResponse->setData([
            'message'       => __('There aren\'t any pending notifications'),
            'message_has'   => sprintf(__('There are pending notifications: %d'), $count),
            'count'         => $count,
            'notifications' => $notifications,
            'hash'          => sha1(implode('', $notifications)),
        ]);

        JsonResponse::factory($this->router->response())->send($jsonResponse);
    }
}
