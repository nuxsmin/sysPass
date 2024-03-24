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

namespace SP\Core;

use JsonException;
use Klein\Klein;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Http\RequestInterface;
use SP\Util\Util;

/**
 * Base module for HTTP based modules
 */
abstract class HttpModuleBase extends ModuleBase
{
    public function __construct(
        Application                         $application,
        ProvidersHelper                     $providersHelper,
        protected readonly RequestInterface $request,
        protected readonly Klein            $router
    ) {
        parent::__construct($application, $providersHelper);
    }

    /**
     * Comprobar si el modo mantenimiento está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     *
     * @throws JsonException
     */
    protected function checkMaintenanceMode(): bool
    {
        if ($this->configData->isMaintenance()) {
            BootstrapBase::$LOCK = Util::getAppLock();

            return !$this->request->isAjax()
                   || !(BootstrapBase::$LOCK !== false
                        && BootstrapBase::$LOCK->userId > 0
                        && $this->context->isLoggedIn()
                        && BootstrapBase::$LOCK->userId === $this->context->getUserData()->getId());
        }

        return false;
    }
}
