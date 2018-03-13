<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\SessionUtil;
use SP\Http\Request;
use SP\Http\Response;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Auth\LoginService;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
class LoginController extends ControllerBase
{
    use JsonTrait;

    /**
     * Login action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function loginAction()
    {
        try {
            $loginService = $this->dic->get(LoginService::class);
            $loginResponmse = $loginService->doLogin();

            $forward = Request::getRequestHeaders('X-Forwarded-For');

            if ($forward) {
                $this->eventDispatcher->notifyEvent('login.info',
                    new Event($this, EventMessage::factory()
                        ->addDetail('X-Forwarded-For', $this->configData->isDemoEnabled() ? '***' : $forward))
                );
            }

            $this->returnJsonResponseData(['url' => $loginResponmse->getRedirect()]);
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponse($e->getCode(), $e->getMessage());
        }
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

            $this->session->setAppStatus(SessionContext::APP_STATUS_LOGGEDOUT);

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

        if ($this->session->isLoggedIn() === true) {
            $this->session->setAppStatus(SessionContext::APP_STATUS_LOGGEDOUT);

            $this->view->assign('loggedOut', 1);
        } else {
            $this->view->assign('loggedOut', 0);
        }

        $this->view->assign('mailEnabled', $this->configData->isMailEnabled());
//        $this->view->assign('updated', SessionFactory::getAppUpdated());

        $this->view();
    }
}