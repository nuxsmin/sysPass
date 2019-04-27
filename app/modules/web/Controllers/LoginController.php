<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\SessionUtil;
use SP\Http\Uri;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Auth\LoginService;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
final class LoginController extends ControllerBase
{
    use JsonTrait;

    /**
     * Login action
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function loginAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $loginService = $this->dic->get(LoginService::class);

            $from = $this->getSignedUriFromRequest();
            $loginService->setFrom($from);

            $loginResponmse = $loginService->doLogin();

            $this->checkForwarded();

            $redirector = function ($route) use ($from) {
                $uri = new Uri(ltrim(Bootstrap::$SUBURI, '/'));
                $uri->addParam('r', $route);

                if ($from !== null) {
                    return $uri->addParam('from', $from)
                        ->getUriSigned($this->configData->getPasswordSalt());
                }

                return $uri->getUri();
            };

            $this->eventDispatcher->notifyEvent('login.finish',
                new Event($this,
                    EventMessage::factory()
                        ->addExtra('redirect', $redirector))
            );

            return $this->returnJsonResponseData([
                'url' => $this->session->getTrasientKey('redirect') ?: $loginResponmse->getRedirect()
            ]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponse($e->getCode(), $e->getMessage());
        }
    }

    /**
     * checkForwarded
     */
    private function checkForwarded()
    {
        $forward = $this->request->getForwardedFor();

        if ($forward !== null) {
            $this->eventDispatcher->notifyEvent('login.info',
                new Event($this, EventMessage::factory()
                    ->addDetail('Forwarded', $this->configData->isDemoEnabled() ? '***' : implode(',', $forward)))
            );
        }
    }

    /**
     * Logout action
     */
    public function logoutAction()
    {
        if ($this->session->isLoggedIn() === true) {
            $inactiveTime = abs(round((time() - $this->session->getLastActivity()) / 60, 2));
            $totalTime = abs(round((time() - $this->session->getStartActivity()) / 60, 2));

            $this->eventDispatcher->notifyEvent('logout',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Logout session'))
                    ->addDetail(__u('User'), $this->session->getUserData()->getLogin())
                    ->addDetail(__u('Inactive time'), $inactiveTime . ' min.')
                    ->addDetail(__u('Total time'), $totalTime . ' min.'))
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function indexAction()
    {
        $this->dic->get(LayoutHelper::class)
            ->getCustomLayout('index', 'login');

        $this->view->assign('mailEnabled', $this->configData->isMailEnabled());

        $this->prepareSignedUriOnView();

        $this->view();
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        // TODO: Implement initialize() method.
    }
}