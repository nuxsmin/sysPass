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
use SP\Domain\Auth\Ports\LoginService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\processException;

/**
 * Class LoginController
 *
 * @package SP\Modules\Web\Controllers
 */
final class LoginController extends ControllerBase
{
    private LoginService $loginService;

    public function __construct(
        WebControllerHelper $webControllerHelper,
        LoginService $loginService
    ) {
        parent::__construct($webControllerHelper);

        $this->loginService = $loginService;
    }

    /**
     * Login action
     */
    #[Action(ResponseType::JSON)]
    public function loginAction(): ActionResponse
    {
        try {
            $from = $this->getSignedUriFromRequest($this->request, $this->configData);

            $loginResponse = $this->loginService->doLogin($from);

            $this->checkForwarded();

            $this->eventDispatcher->notify(
                'login.finish',
                new Event($this)
            );

            return ActionResponse::ok(
                '',
                [
                    'url' => $this->session->getTrasientKey('redirect') ?: $loginResponse->getRedirect(),
                ]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return ActionResponse::error($e->getMessage());
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
                    EventMessage::build()
                                ->addDetail(
                                    'Forwarded',
                                    $this->configData->isDemoEnabled() ? '***' : implode(',', $forward)
                                )
                )
            );
        }
    }
}
