<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Core;

use SP\Storage\MySQLHandler;
use SP\Storage\DBStorageInterface;
use SP\Storage\FileStorageInterface;
use SP\Storage\XmlHandler;

/**
 * Class Factory
 *
 * @package SP\Core
 */
class Factory
{
    /**
     * @var FileStorageInterface
     */
    private static $configFactory;
    /**
     * @var DBStorageInterface
     */
    private static $DBFactory;

    /**
     * Devuelve el almacenamiento para la configuración
     *
     * @return FileStorageInterface
     */
    public static function getConfigStorage(){
        if (!self::$configFactory instanceof FileStorageInterface) {
            self::$configFactory = new XmlHandler(XML_CONFIG_FILE);
        }

        return self::$configFactory;
    }

    /**
     * Devuelve el manejador para la BD
     *
     * @return DBStorageInterface
     */
    public static function getDBStorage()
    {
        if (!self::$DBFactory instanceof DBStorageInterface) {
            self::$DBFactory = new MySQLHandler();
        }

        return self::$DBFactory;
    }
}