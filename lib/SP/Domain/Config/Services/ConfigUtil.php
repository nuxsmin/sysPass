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

namespace SP\Domain\Config\Services;

use SP\Domain\Common\Providers\Environment;
use SP\Domain\Core\Exceptions\ConfigException;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__;
use function SP\__u;

/**
 * Class ConfigUtil
 */
final class ConfigUtil
{

    /**
     * Adaptador para convertir una cadena de direcciones de email a un array
     */
    public static function mailAddressesAdapter(string $mailAddresses): array
    {
        if (empty($mailAddresses)) {
            return [];
        }

        return array_filter(
            explode(',', $mailAddresses),
            static fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL)
        );
    }

    /**
     * Adaptador para convertir una cadena de eventos a un array
     */
    public static function eventsAdapter(array $events): array
    {
        return array_filter(
            $events,
            static fn($value) => preg_match('/^[a-z][a-z.]+$/i', $value)
        );
    }

    /**
     * Comprobar el archivo de configuración.
     * Esta función comprueba que el archivo de configuración exista y los permisos sean correctos.
     *
     * @throws ConfigException
     */
    public static function checkConfigDir(): void
    {
        if (!is_dir(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(__u('\'/app/config\' directory does not exist.'), SPException::CRITICAL);
        }

        if (!is_writable(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(__u('Unable to write into \'/app/config\' directory'), SPException::CRITICAL);
        }

        if (!Environment::checkIsWindows()
            && ($configPerms = decoct(fileperms(CONFIG_PATH) & 0777)) !== '750'
        ) {
            clearstatcache();

            throw new ConfigException(
                __u('\'/app/config\' directory permissions are wrong'),
                SPException::ERROR,
                sprintf(__('Current: %s - Needed: 750'), $configPerms)
            );
        }
    }
}
