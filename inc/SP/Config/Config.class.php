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

use ReflectionObject;
use SP\Core\DiFactory;
use SP\Core\Session;

defined('APP_ROOT') || die();

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 */
class Config
{
    /**
     * @var ConfigData
     */
    private static $Config;
    /**
     * @var bool
     */
    private static $configLoaded = false;

    /**
     * Cargar la configuración desde el archivo
     *
     * @param bool $reload
     * @return ConfigData
     */
    public static function loadConfig($reload = false)
    {
        if ($reload === false && self::$configLoaded) {
            return self::$Config;
        }

        $ConfigData = Session::getConfig();

        if ($reload === true
            || !is_object($ConfigData)
            || time() >= (Session::getConfigTime() + $ConfigData->getSessionTimeout() / 2)
        ) {
            self::saveConfigInSession();
        }

        return self::$Config;
    }

    /**
     * Guardar la configuración en la sesión
     */
    private static function saveConfigInSession()
    {
        Session::setConfig(self::$Config);
        Session::setConfigTime(time());
    }

    /**
     * Guardar la configuración
     *
     * @param ConfigData $Config
     * @param bool       $backup
     */
    public static function saveConfig(ConfigData $Config = null, $backup = true)
    {
        $ConfigData = null === $Config ? self::getConfig() : $Config;
        $ConfigData->setConfigDate(time());
        $ConfigData->setConfigSaver(Session::getUserData()->getUserLogin());
        $ConfigData->setConfigHash();

        DiFactory::getConfigStorage()->setItems($ConfigData);
        DiFactory::getConfigStorage()->save('config');

        if ($backup) {
            self::backupToDB();
        }
    }

    /**
     * Obtener la configuración o devolver una nueva
     *
     * @return ConfigData
     */
    public static function getConfig()
    {
        if (self::$configLoaded) {
            return self::$Config;
        }

        $ConfigData = Session::getConfig();

        self::$Config = $ConfigData instanceof ConfigData ? $ConfigData : self::loadConfigFile();
        self::$configLoaded = true;

        self::saveConfigInSession();

        return self::$Config;
    }

    /**
     * Cargar el archivo de configuración
     *
     * @return ConfigData
     */
    private static function loadConfigFile()
    {
        self::$Config = new ConfigData();

        if (!file_exists(XML_CONFIG_FILE)) {
            return self::$Config;
        }

        try {
            // Mapear el array de elementos de configuración con las propieades de la clase ConfigData
            $items = DiFactory::getConfigStorage()->load('config')->getItems();
            $Reflection = new ReflectionObject(self::$Config);

            foreach ($Reflection->getProperties() as $property) {
                $property->setAccessible(true);

                if (isset($items[$property->getName()])) {
                    $property->setValue(self::$Config, $items[$property->getName()]);
                }

                $property->setAccessible(false);
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage());
        }

        return self::$Config;
    }

    /**
     * Realizar un backup de la configuración en la BD
     */
    private static function backupToDB()
    {
        $config = json_encode(self::getConfig());
        ConfigDB::setValue('config_backup', $config, true, true);
        ConfigDB::setValue('config_backupdate', time());
    }

    /**
     * Restaurar la configuración desde la BD
     *
     * @return array
     */
    private static function restoreBackupFromDB()
    {
        $configBackup = ConfigDB::getValue('config_backup');

        return json_decode($configBackup);
    }
}
