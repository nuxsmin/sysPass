<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\DataModel\NotificationData;
use SP\Domain\Notification\NotificationServiceInterface;
use SP\Html\Html;
use SP\Http\Json;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class NotificationsController
 */
final class NotificationsController extends SimpleControllerBase
{
    private NotificationServiceInterface $notificationService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        NotificationServiceInterface $notificationService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->checks();

        $this->notificationService = $notificationService;
    }


    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function notificationsAction(): void
    {
        $notifications = array_map(
            static function ($notification) {
                /** @@var $notification NotificationData */
                return sprintf(
                    '(%s) - %s',
                    $notification->getComponent(),
                    Html::truncate(Html::stripTags($notification->getDescription()), 30)
                );
            },
            $this->notificationService->getAllActiveForUserId($this->session->getUserData()->getId())
        );

        $count = count($notifications);

        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus(0);
        $jsonResponse->setData([
            'message'       => __('There aren\'t any pending notifications'),
            'message_has'   => sprintf(__('There are pending notifications: %d'), $count),
            'count'         => $count,
            'notifications' => $notifications,
            'hash'          => sha1(implode('', $notifications)),
        ]);

        Json::factory($this->router->response())->returnJson($jsonResponse);
    }
}