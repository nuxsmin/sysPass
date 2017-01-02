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

namespace SP\Core\Plugin;

use SP\Core\Session;
use SP\DataModel\PluginData;
use SP\Log\Log;
use SP\Mgmt\Plugins\Plugin;

/**
 * Class PluginUtil
 *
 * @package SP\Core\Plugin
 */
class PluginUtil
{
    /**
     * @var array Plugins ya cargados
     */
    private static $loadedPlugins = [];
    /**
     * @var array Plugins deshabilitados
     */
    private static $disabledPlugins = [];

    /**
     * Devuelve la lista de Plugins disponibles
     *
     * @return array
     */
    public static function getPlugins()
    {
        $pluginDirH = opendir(PLUGINS_PATH);
        $plugins = [];

        if ($pluginDirH) {
            while (false !== ($entry = readdir($pluginDirH))) {
                if ($entry !== '.' && $entry !== '..') {
                    $plugins[] = $entry;
                }
            }

            closedir($pluginDirH);
        }

        return $plugins;
    }

    /**
     * Cargar un plugin
     *
     * @param string $name Nombre del plugin
     * @return bool|PluginInterface
     */
    public static function loadPlugin($name)
    {
        $name = ucfirst($name);

        if (in_array($name, Session::getPluginsDisabled())) {
            return false;
        }

        $pluginClass = 'Plugins\\' . $name . '\\' . $name . 'Plugin';

        if (isset(self::$loadedPlugins[$name])) {
            return self::$loadedPlugins[$name];
        }

        try {
            $PluginData = self::getDataFromDb($name);

            if ($PluginData->getPluginEnabled() === 0) {
                self::$disabledPlugins[] = $name;
                return false;
            }

            $Reflection = new \ReflectionClass($pluginClass);

            /** @var PluginBase $Plugin */
            $Plugin = $Reflection->newInstance();
            $Plugin->setData(unserialize($PluginData->getPluginData()));
            $Plugin->init();

            self::$loadedPlugins[$name] = $Plugin;

            return $Plugin;
        } catch (\ReflectionException $e) {
            Log::writeNewLog(__FUNCTION__, sprintf(_('No es posible cargar el plugin "%s"'), $name));
        }

        return false;
    }

    /**
     * Obtiene los datos del plugin desde la BD
     *
     * @param $pluginName
     * @return PluginData
     */
    public static function getDataFromDb($pluginName)
    {
        $PluginData = Plugin::getItem()->getByName($pluginName);

        if (!is_object($PluginData)) {
            $PluginData = new PluginData();
            $PluginData->setPluginName($pluginName);
            $PluginData->setPluginEnabled(0);

            Plugin::getItem($PluginData)->add();
        }

        return $PluginData;
    }

    /**
     * Devolver los plugins cargados
     *
     * @return PluginInterface[]
     */
    public static function getLoadedPlugins()
    {
        return self::$loadedPlugins;
    }

    /**
     * Devolver los plugins deshabilidatos
     *
     * @return array
     */
    public static function getDisabledPlugins()
    {
        return self::$disabledPlugins;
    }
}