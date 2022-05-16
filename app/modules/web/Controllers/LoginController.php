<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

use Exception;
use Klein\Klein;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Context\ContextBase;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\PhpExtensionChecker;
use SP\Core\SessionUtil;
use SP\Core\UI\ThemeInterface;
use SP\Http\Request;
use SP\Http\Uri;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Browser\Browser;
use SP\Services\Auth\LoginService;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
final class LoginController extends ControllerBase
{
    use JsonTrait;

    private LoginService $loginService;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        Request $request,
        PhpExtensionChecker $extensionChecker,
        Template $template,
        Browser $browser,
        LayoutHelper $layoutHelper,
        LoginService $loginService
    ) {
        parent::__construct(
            $application,
            $theme,
            $router,
            $acl,
            $request,
            $extensionChecker,
            $template,
            $browser,
            $layoutHelper
        );

        $this->loginService = $loginService;
    }


    /**
     * Login action
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \JsonException
     */
    public function loginAction(): bool
    {
        try {
            $from = $this->getSignedUriFromRequest($this->request);
            $this->loginService->setFrom($from);

            $loginResponse = $this->loginService->doLogin();

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

            $this->eventDispatcher->notifyEvent(
                'login.finish',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addExtra('redirect', $redirector)
                )
            );

            return $this->returnJsonResponseData([
                'url' => $this->session->getTrasientKey('redirect')
                    ?: $loginResponse->getRedirect(),
            ]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponse($e->getCode(), $e->getMessage());
        }
    }

    /**
     * checkForwarded
     */
    private function checkForwarded(): void
    {
        $forward = $this->request->getForwardedFor();

        if ($forward !== null) {
            $this->eventDispatcher->notifyEvent(
                'login.info',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDetail('Forwarded', $this->configData->isDemoEnabled() ? '***' : implode(',', $forward))
                )
            );
        }
    }

    /**
     * Logout action
     */
    public function logoutAction(): void
    {
        if ($this->session->isLoggedIn() === true) {
            $inactiveTime = abs(round((time() - $this->session->getLastActivity()) / 60, 2));
            $totalTime = abs(round((time() - $this->session->getStartActivity()) / 60, 2));

            $this->eventDispatcher->notifyEvent(
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

            SessionUtil::cleanSession();

            $this->session->setAppStatus(ContextBase::APP_STATUS_LOGGEDOUT);

            $this->layoutHelper->getCustomLayout('logout', 'logout');

            $this->view();
        } else {
            $this->router->response()->redirect('index.php?r=login');
        }
    }

    /**
     * Index action
     */
    public function indexAction(): void
    {
        SessionUtil::cleanSession();

        $this->layoutHelper->getCustomLayout('index', 'login');

        $this->view->assign(
            'mailEnabled',
            $this->configData->isMailEnabled()
        );

        $this->prepareSignedUriOnView();

        $this->view();
    }
}