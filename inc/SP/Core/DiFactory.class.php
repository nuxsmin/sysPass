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

use SP\Core\UI\Theme;
use SP\Core\UI\ThemeInterface;
use SP\Mgmt\ItemBase;
use SP\Storage\MySQLHandler;
use SP\Storage\DBStorageInterface;
use SP\Storage\FileStorageInterface;
use SP\Storage\XmlHandler;

/**
 * Class SingleFactory
 *
 * @package SP\Core
 */
class DiFactory
{
    /**
     * @var FileStorageInterface
     */
    private static $ConfigFactory;
    /**
     * @var DBStorageInterface
     */
    private static $DBFactory;
    /**
     * @var ItemBase[]
     */
    private static $ItemFactory = [];
    /**
     * @var ThemeInterface
     */
    private static $ThemeFactory;

    /**
     * Devuelve el almacenamiento para la configuración
     *
     * @return FileStorageInterface
     */
    public static final function getConfigStorage()
    {
        if (!self::$ConfigFactory instanceof FileStorageInterface) {
            self::$ConfigFactory = new XmlHandler(XML_CONFIG_FILE);
        }

        return self::$ConfigFactory;
    }

    /**
     * Devuelve el manejador para la BD
     *
     * @return DBStorageInterface
     */
    public static final function getDBStorage()
    {
        if (!self::$DBFactory instanceof DBStorageInterface) {
            self::$DBFactory = new MySQLHandler();
        }

        return self::$DBFactory;
    }

    /**
     * Devuelve la instancia de la clase del elemento solicitado
     *
     * @param  string $caller   La clase del objeto
     * @param  object $itemData Los datos del elemento
     * @return object
     * @throws Exceptions\InvalidClassException
     */
    public static final function getItem($caller, $itemData = null)
    {
//        error_log(count(self::$ItemFactory) . '-' . (memory_get_usage() / 1000));

        if (isset(self::$ItemFactory[$caller])) {
            return (null !== $itemData) ? self::$ItemFactory[$caller]->setItemData($itemData) : self::$ItemFactory[$caller];
        }

        self::$ItemFactory[$caller] = new $caller($itemData);

        return self::$ItemFactory[$caller];
    }

    /**
     * Devuelve el manejador para el tema visual
     *
     * @return ThemeInterface
     */
    public static final function getTheme()
    {
        if (!self::$ThemeFactory instanceof Theme) {
            self::$ThemeFactory = new Theme();
        }

        return self::$ThemeFactory;
    }
}