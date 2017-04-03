<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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
     * Cargar la configuración desde el archivo
     *
     * @param bool $reload
     */
    public static function loadConfig($reload = false)
    {
        $ConfigData = self::$Config instanceof ConfigData ? self::$Config : Session::getConfig();

        if ($reload === true
            || !is_object($ConfigData)
            || time() >= (Session::getConfigTime() + $ConfigData->getSessionTimeout() / 2)
        ) {
            Session::setConfig(self::arrayMapper());
            Session::setConfigTime(time());
        }
    }

    /**
     * Mapear el array de elementos de configuración con las propieades de la
     * clase ConfigData
     *
     * @return ConfigData
     */
    private static function arrayMapper()
    {
        if (self::$Config instanceof ConfigData) {
            return self::$Config;
        }

        self::$Config = new ConfigData();

        if (!file_exists(XML_CONFIG_FILE)) {
            return self::$Config;
        }

        try {
            $items = DiFactory::getConfigStorage()->load('config')->getItems();
            $Reflection = new ReflectionObject(self::$Config);

            foreach ($Reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $property->setValue(self::$Config, @$items[$property->getName()]);
                $property->setAccessible(false);
            }
        } catch (\Exception $e) {
        }

        return self::$Config;
    }

    /**
     * @param ConfigData $Config
     * @param bool $backup
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
        if (self::$Config instanceof ConfigData) {
            return self::$Config;
        }

        $ConfigData = Session::getConfig();

        self::$Config = $ConfigData instanceof ConfigData ? $ConfigData : self::arrayMapper();

        return self::$Config;
    }

    /**
     * Realizar un backup de la configuración en la BD
     *
     * @throws \SP\Core\Exceptions\SPException
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
