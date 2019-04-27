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

namespace SP\Modules\Web\Controllers\Traits;

use Exception;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Http\JsonResponse;
use SP\Util\Util;

/**
 * Trait ConfigTrait
 *
 * @package SP\Modules\Web\Controllers\Traits
 */
trait ConfigTrait
{
    use JsonTrait;

    /**
     * Guardar la configuración
     *
     * @param ConfigData    $configData
     * @param Config        $config
     * @param callable|null $onSuccess
     *
     * @return bool
     */
    protected function saveConfig(ConfigData $configData, Config $config, callable $onSuccess = null)
    {
        try {
            if ($configData->isDemoEnabled()) {
                return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
            }

            $config->saveConfig($configData);

            if ($configData->isMaintenance() === false && Bootstrap::$LOCK !== false) {
                Util::unlockApp();
            }

            if ($onSuccess !== null) {
                $onSuccess();
            }

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Configuration updated'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Error while saving the configuration'));
        }
    }
}