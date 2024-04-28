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

namespace SP\Modules\Web\Controllers\Login;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Ports\LoginService;
use SP\Domain\Auth\Services\Login;
use SP\Domain\Http\Providers\Uri;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
final class LoginController extends ControllerBase
{
    use JsonTrait;

    private Login $loginService;

    public function __construct(
        Application  $application,
        WebControllerHelper $webControllerHelper,
        LoginService $loginService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->loginService = $loginService;
    }

    /**
     * Login action
     *
     * @return bool
     * @throws JsonException
     */
    public function loginAction(): bool
    {
        try {
            $from = $this->getSignedUriFromRequest($this->request, $this->configData);

            $loginResponse = $this->loginService->doLogin($from);

            $this->checkForwarded();

            $redirector = function ($route) use ($from) {
                $uri = new Uri(ltrim($this->uriContext->getSubUri(), '/'));
                $uri->addParam('r', $route);

                if ($from !== null) {
                    return $uri->addParam('from', $from)->getUriSigned($this->configData->getPasswordSalt());
                }

                return $uri->getUri();
            };

            $this->eventDispatcher->notify(
                'login.finish',
                new Event($this, EventMessage::factory()->addExtra('redirect', $redirector))
            );

            return $this->returnJsonResponseData([
                                                     'url' => $this->session->getTrasientKey(
                                                         'redirect'
                                                     ) ?: $loginResponse->getRedirect(),
                                                 ]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

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
            $this->eventDispatcher->notify(
                'login.info',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDetail(
                            'Forwarded',
                            $this->configData->isDemoEnabled() ? '***' : implode(',', $forward)
                        )
                )
            );
        }
    }
}
