<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Login;

use SP\Core\Context\ContextBase;
use SP\Core\Context\SessionLifecycleHandler;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Modules\Web\Controllers\ControllerBase;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
final class LogoutController extends ControllerBase
{
    /**
     * Logout action
     */
    public function logoutAction(): void
    {
        if ($this->session->isLoggedIn() === true) {
            $inactiveTime = abs(round((time() - $this->session->getLastActivity()) / 60, 2));
            $totalTime = abs(round((time() - $this->session->getStartActivity()) / 60, 2));

            $this->eventDispatcher->notify(
                'logout',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Logout session'))
                        ->addDetail(__u('User'), $this->session->getUserData()->getLogin())
                        ->addDetail(__u('Inactive time'), $inactiveTime.' min.')
                        ->addDetail(__u('Total time'), $totalTime.' min.')
                )
            );

            SessionLifecycleHandler::clean();

            $this->session->setAppStatus(ContextBase::APP_STATUS_LOGGEDOUT);

            $this->layoutHelper->getCustomLayout('logout', 'logout');

            $this->view();
        } else {
            $this->router->response()->redirect('index.php?r=login');
        }
    }
}
