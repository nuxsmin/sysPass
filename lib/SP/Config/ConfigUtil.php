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

namespace SP\Config;

use SP\Core\Exceptions\ConfigException;
use SP\Util\Checks;

/**
 * Class ConfigUtil
 *
 * @package Config
 */
final class ConfigUtil
{
    /**
     * Adaptador para convertir una cadena de extensiones a un array
     *
     * @param string $filesAllowedExts
     *
     * @return array
     */
    public static function filesExtsAdapter($filesAllowedExts)
    {
        if (empty($filesAllowedExts)) {
            return [];
        }

        return array_map(function ($value) {
            if (preg_match('/[^a-z0-9_-]+/i', $value)) {
                return null;
            }

            return strtoupper($value);
        }, explode(',', $filesAllowedExts));
    }

    /**
     * Adaptador para convertir una cadena de direcciones de email a un array
     *
     * @param string $mailAddresses
     *
     * @return array
     */
    public static function mailAddressesAdapter($mailAddresses)
    {
        if (empty($mailAddresses)) {
            return [];
        }

        return array_filter(explode(',', $mailAddresses), function ($value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        });
    }

    /**
     * Adaptador para convertir una cadena de eventos a un array
     *
     * @param array $events
     *
     * @return array
     */
    public static function eventsAdapter(array $events)
    {
        return array_filter($events, function ($value) {
            return preg_match('/^[a-z][a-z\.]+$/i', $value);
        });
    }

    /**
     * Comprobar el archivo de configuración.
     * Esta función comprueba que el archivo de configuración exista y los permisos sean correctos.
     *
     * @throws ConfigException
     */
    public static function checkConfigDir()
    {
        if (!is_dir(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(__u('\'/app/config\' directory does not exist.'), ConfigException::CRITICAL);
        }

        if (!is_writable(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(__u('Unable to write into \'/app/config\' directory'), ConfigException::CRITICAL);
        }

        if (!Checks::checkIsWindows()
            && ($configPerms = decoct(fileperms(CONFIG_PATH) & 0777)) !== '750'
        ) {
            clearstatcache();

            throw new ConfigException(
                __u('\'/app/config\' directory permissions are wrong'),
                ConfigException::ERROR,
                sprintf(__('Current: %s - Needed: 750'), $configPerms));
        }
    }
}