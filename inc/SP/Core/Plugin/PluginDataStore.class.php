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

namespace SP\Core\Plugin;

use SP\DataModel\PluginData;
use SP\Log\Log;
use SP\Mgmt\Plugins\Plugin;

/**
 * Class PluginDataStore
 *
 * @package SP\Core\Plugin
 */
class PluginDataStore
{
    /**
     * Guardar los datos de un plugin
     *
     * @param PluginInterface $Plugin
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function save(PluginInterface $Plugin)
    {
        $PluginData = new PluginData();
        $PluginData->setPluginName($Plugin->getName());
        $PluginData->setPluginEnabled(1);
        $PluginData->setPluginData(serialize($Plugin->getData()));

        Plugin::getItem($PluginData)->update();
    }

    /**
     * Cargar los datos de un plugin
     *
     * @param PluginInterface $Plugin
     * @return bool
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function load(PluginInterface $Plugin)
    {
        /** @var PluginData $PluginData */
        $PluginData = Plugin::getItem()->getByName($Plugin->getName());

        if (!is_object($PluginData)) {
            $PluginData = new PluginData();
            $PluginData->setPluginName($Plugin->getName());
            $PluginData->setPluginEnabled(0);

            Plugin::getItem($PluginData)->add();

            $Log = new Log();
            $Log->getLogMessage()
                ->setAction(__('Nuevo Plugin', false))
                ->addDetails(__('Nombre', false), $Plugin->getName());
            $Log->writeLog();

            return false;
        }

        $data = $PluginData->getPluginData();

        if ($data !== '') {
            $Plugin->setData(unserialize($PluginData->getPluginData()));
        }

        return (bool)$PluginData->getPluginEnabled();
    }
}