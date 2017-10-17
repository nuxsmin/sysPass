<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
class ConfigUtil
{
    /**
     * Adaptador para convertir una cadena de extensiones a un array
     *
     * @param $filesAllowedExts
     * @return array
     */
    public static function filesExtsAdapter(&$filesAllowedExts)
    {
        $exts = explode(',', $filesAllowedExts);

        array_walk($exts, function (&$value) {
            if (preg_match('/[^a-z0-9_-]+/i', $value)) {
                $value = null;
            }
        });

        return $exts;
    }

    /**
     * Comprobar el archivo de configuración.
     * Esta función comprueba que el archivo de configuración exista y los permisos sean correctos.
     *
     * @throws \SP\Core\Exceptions\ConfigException
     */
    public static function checkConfigDir()
    {
        if (!is_dir(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(ConfigException::SP_CRITICAL, __('El directorio "/config" no existe', false));
        }

        if (!is_writable(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(ConfigException::SP_CRITICAL, __('No es posible escribir en el directorio "config"', false));
        }

        $configPerms = decoct(fileperms(CONFIG_PATH) & 0777);

        if ($configPerms !== '750' && !Checks::checkIsWindows()) {
            clearstatcache();

            throw new ConfigException(
                ConfigException::SP_ERROR,
                __('Los permisos del directorio "/config" son incorrectos', false),
                __('Actual:', false) . ' ' . $configPerms . ' - ' . __('Necesario: 750', false));
        }
    }
}