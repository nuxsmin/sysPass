<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\SessionFactory;
use SP\Core\SessionUtil;
use SP\Html\Html;
use SP\Http\Response;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Services\Auth\LoginService;
use SP\Util\Json;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
class LoginController extends ControllerBase
{
    /**
     * Login action
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function loginAction()
    {
        $loginService = $this->dic->get(LoginService::class);
        Json::returnJson($loginService->doLogin());
    }

    /**
     * Logout action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function logoutAction()
    {
        if ($this->session->isLoggedIn()) {
            $inactiveTime = abs(round((time() - $this->session->getLastActivity()) / 60, 2));
            $totalTime = abs(round((time() - $this->session->getStartActivity()) / 60, 2));

            $this->eventDispatcher->notifyEvent('logout',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Finalizar sesión'))
                    ->addDetail(__u('Usuario'), $this->session->getUserData()->getLogin())
                    ->addDetail(__u('Tiempo inactivo'), $inactiveTime . ' min.')
                    ->addDetail(__u('Tiempo total'), $totalTime . ' min.'))
            );

            SessionUtil::cleanSession();

            $this->session->setLoggedOut(true);

            $layoutHelper = $this->dic->get(LayoutHelper::class);
            $layoutHelper->getCustomLayout('logout', 'logout');

            $this->view();
        } else {
            Response::redirect('index.php?r=login');
        }
    }

    /**
     * Index action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function indexAction()
    {
        $layoutHelper = $this->dic->get(LayoutHelper::class);
        $layoutHelper->getCustomLayout('index', 'login');

        if (SessionFactory::getLoggedOut() === true) {
            SessionFactory::setLoggedOut();

            $this->view->assign('loggedOut', 1);
        } else {
            $this->view->assign('loggedOut', 0);
        }

        $this->view->assign('mailEnabled', $this->configData->isMailEnabled());
        $this->view->assign('updated', SessionFactory::getAppUpdated());

        SessionFactory::setAppUpdated(false);

        $getParams = [];

        // Comprobar y parsear los parámetros GET para pasarlos como POST en los inputs
        if (count($_GET) > 0) {
            foreach ($_GET as $param => $value) {
                $getParams['g_' . Html::sanitizeFull($param)] = Html::sanitizeFull($value);
            }
        }

        $this->view->assign('getParams', $getParams);
        $this->view();
    }
}