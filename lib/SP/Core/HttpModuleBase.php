<?php
declare(strict_types=1);
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

namespace SP\Core;

use Klein\Klein;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Ports\RequestService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;

/**
 * Base module for HTTP based modules
 */
abstract class HttpModuleBase extends ModuleBase
{
    public function __construct(
        Application                       $application,
        ProvidersHelper                   $providersHelper,
        protected readonly RequestService $request,
        protected readonly Klein          $router
    ) {
        parent::__construct($application, $providersHelper);
    }

    /**
     * Comprobar si el modo mantenimiento está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     *
     * @return bool
     * @throws SPException
     */
    protected function checkMaintenanceMode(): bool
    {
        if ($this->configData->isMaintenance()) {
            BootstrapBase::$LOCK = self::getAppLock();

            return !$this->request->isAjax()
                   || !(BootstrapBase::$LOCK !== false
                        && BootstrapBase::$LOCK->userId > 0
                        && $this->context->isLoggedIn()
                        && BootstrapBase::$LOCK->userId === $this->context->getUserData()->getId());
        }

        return false;
    }

    /**
     * Comprueba si la aplicación está bloqueada
     *
     * @return bool|string
     * @throws SPException
     */
    private static function getAppLock(): bool|string
    {
        try {
            $file = new FileHandler(LOCK_FILE);

            return Serde::deserializeJson($file->readToString());
        } catch (FileException) {
            return false;
        }
    }
}
