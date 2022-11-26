<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Exceptions\SPException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Http\RequestInterface;
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
    final protected function getSignedUriFromRequest(
        RequestInterface $request,
        ConfigDataInterface $configData
    ): ?string {
        if (!$this->setup) {
            return null;
        }

        $from = $request->analyzeString('from');

        if ($from) {
            try {
                $request->verifySignature($configData->getPasswordSalt(), 'from');
            } catch (SPException $e) {
                processException($e);

                $from = null;
            }
        }

        return $from;
    }

    /**
     * @throws \SP\Core\Exceptions\SPException
     */
    private function handleSessionTimeout(): void
    {
        $this->sessionLogout(
            $this->request,
            $this->configData,
            fn($redirect) => $this->router->response()->redirect($redirect)->send(true)
        );
    }
}
