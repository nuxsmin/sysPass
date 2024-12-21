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
use SP\Domain\Notification\Ports\NotificationService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Forms\NotificationForm;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class NotificationSaveBase
 */
abstract class NotificationSaveBase extends ControllerBase
{
    protected NotificationService $notificationService;
    protected NotificationForm    $form;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        NotificationService $notificationService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->notificationService = $notificationService;
        $this->form = new NotificationForm($application, $this->request);
    }

}
