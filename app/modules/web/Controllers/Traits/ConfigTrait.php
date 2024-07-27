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

namespace SP\Modules\Web\Controllers\Traits;

use Exception;
use JsonException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;

use function SP\__u;
use function SP\processException;

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
     * @throws JsonException
     * @throws SPException
     */
    protected function saveConfig(
        ConfigDataInterface $configData,
        ConfigFileService $config,
        callable          $onSuccess = null
    ): bool {
        try {
            if ($configData->isDemoEnabled()) {
                return $this->returnJsonResponse(JsonMessage::JSON_WARNING, __u('Ey, this is a DEMO!!'));
            }

            $config->save($configData);

            if ($onSuccess !== null) {
                $onSuccess();
            }

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Configuration updated'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponse(
                JsonMessage::JSON_ERROR,
                __u('Error while saving the configuration'),
                [$e]
            );
        }
    }
}
