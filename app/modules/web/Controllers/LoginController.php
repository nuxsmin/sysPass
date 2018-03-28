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
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\SessionUtil;
use SP\Http\Request;
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
            $from = Request::analyzeString('from');

            $loginService = $this->dic->get(LoginService::class);

            if ($from && Hash::checkMessage($from, $this->configData->getPasswordSalt(), Request::analyzeString('h'))) {
                $loginService->setFrom($from);
            }

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
            $this->router->response()->redirect('index.php?r=login');
        }
    }

    /**
     * Index action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function indexAction()
    {
        if ($this->session->isLoggedIn() === true) {
            $this->router->response()
                ->redirect('index.php?r=index')
                ->send(true);
        }
        
        $this->dic->get(LayoutHelper::class)
            ->getCustomLayout('index', 'login');

        $this->view->assign('mailEnabled', $this->configData->isMailEnabled());
//        $this->view->assign('updated', SessionFactory::getAppUpdated());

        $from = Request::analyzeString('from');
        $hash = Request::analyzeString('h');

        if ($from && Hash::checkMessage($from, $this->configData->getPasswordSalt(), $hash)) {
            $this->view->assign('from', $from);
            $this->view->assign('from_hash', $hash);
        }

        $this->view();
    }
}