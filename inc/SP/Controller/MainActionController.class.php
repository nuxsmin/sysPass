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

namespace SP\Controller;

use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Upgrade\Upgrade;
use SP\Http\Request;
use SP\Util\Util;

/**
 * Class MainActionController
 *
 * @package SP\Controller
 */
class MainActionController
{
    /**
     * Acción para actualizar lda BD
     *
     * @param $dbVersion
     * @return bool
     */
    public function upgradeDbAction($dbVersion)
    {
        $action = Request::analyze('a');
        $hash = Request::analyze('h');
        $confirm = Request::analyze('chkConfirm', false, false, true);

        if ($confirm === true
            && $action === 'upgrade'
            && $hash === Config::getConfig()->getUpgradeKey()
        ) {
            $this->upgrade($dbVersion, 'db');

            ConfigDB::setValue('version', implode(Util::getVersion(true)));
        } else {
            $controller = new MainController();
            $controller->getUpgrade($dbVersion);
        }

        return false;
    }

    /**
     * Acción para actualizar la aplicación
     *
     * @param $appVersion
     * @return bool
     */
    public function upgradeAppAction($appVersion)
    {
        $action = Request::analyze('a');
        $hash = Request::analyze('h');
        $confirm = Request::analyze('chkConfirm', false, false, true);

        if ($confirm === true
            && $action === 'upgrade'
            && $hash === Config::getConfig()->getUpgradeKey()
        ) {
            $this->upgrade($appVersion, 'app');
        } else {
            $controller = new MainController();
            $controller->getUpgrade($appVersion);
        }

        return false;
    }

    /**
     * Actualizar
     *
     * @param int $version
     * @param int $type
     * @return bool
     */
    private function upgrade($version, $type)
    {
        try {
            Upgrade::doUpgrade($version, $type);

            $Config = Config::getConfig();
            $Config->setMaintenance(false);
            $Config->setUpgradeKey('');

            if ($type === 'app') {
                $Config->setConfigVersion(implode('', Util::getVersion(true)));
            }

            Config::saveConfig($Config);

            Config::loadConfig(true);
            return true;
        } catch (\Exception $e) {
            Init::initError($e->getMessage(), $e->getCode());
        }

        return false;
    }
}