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

namespace SP\Modules\Web\Controllers\Notification;


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\NotificationData;
use SP\Domain\Notification\Ports\NotificationServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class NotificationViewBase
 */
abstract class NotificationViewBase extends ControllerBase
{
    private NotificationServiceInterface $notificationService;
    private UserServiceInterface         $userService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        \SP\Domain\Notification\Ports\NotificationServiceInterface $notificationService,
        UserServiceInterface $userService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->notificationService = $notificationService;
        $this->userService = $userService;
    }

    /**
     * Sets view data for displaying notification's data
     *
     * @param  int|null  $notificationId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    protected function setViewData(?int $notificationId = null): void
    {
        $this->view->addTemplate('notification');

        $notification = $notificationId
            ? $this->notificationService->getById($notificationId)
            : new NotificationData();

        $this->view->assign('notification', $notification);

        if ($this->userData->getIsAdminApp()) {
            $this->view->assign(
                'users',
                SelectItemAdapter::factory($this->userService->getAllBasic())
                    ->getItemsFromModelSelected([$notification->userId])
            );
        }

        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::NOTIFICATION));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
