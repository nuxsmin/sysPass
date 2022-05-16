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

namespace SP\Modules\Web\Controllers\Traits;

use Closure;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\Request;
use SP\Mvc\Controller\ControllerTrait;

/**
 * Trait ControllerTratit
 */
trait WebControllerTrait
{
    use ControllerTrait;

    private bool $setup = false;

    /**
     * Returns the signed URI component after validating its signature.
     * This component is used for deep linking
     */
    final protected function getSignedUriFromRequest(Request $request): ?string
    {
        if (!$this->setup) {
            return null;
        }

        $from = $request->analyzeString('from');

        if ($from) {
            try {
                $request->verifySignature(
                    $this->configData->getPasswordSalt(),
                    'from'
                );
            } catch (SPException $e) {
                processException($e);

                $from = null;
            }
        }

        return $from;
    }

    /**
     * @throws \JsonException
     * @throws SessionTimeout
     */
    private function handleSessionTimeout(Closure $checker): void
    {
        if ($checker->call($this) === true) {
            $this->sessionLogout(
                $this->request,
                function ($redirect) {
                    $this->router->response()
                        ->redirect($redirect)
                        ->send(true);
                }
            );

            throw new SessionTimeout();
        }
    }
}