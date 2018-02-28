<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

            throw new ConfigException(__u('El directorio "/config" no existe'), ConfigException::CRITICAL);
        }

        if (!is_writable(CONFIG_PATH)) {
            clearstatcache();

            throw new ConfigException(__u('No es posible escribir en el directorio "config"'), ConfigException::CRITICAL);
        }

        if (!Checks::checkIsWindows()
            && ($configPerms = decoct(fileperms(CONFIG_PATH) & 0777)) !== '750'
        ) {
            clearstatcache();

            throw new ConfigException(
                __u('Los permisos del directorio "/config" son incorrectos'),
                ConfigException::ERROR,
                sprintf(__('Actual: %s - Necesario: 750'), $configPerms));
        }
    }
}