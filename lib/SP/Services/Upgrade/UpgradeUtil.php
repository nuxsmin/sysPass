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

namespace SP\Services\Upgrade;
use SP\Config\Config;
use SP\Util\Util;

/**
 * Class UpgradeUtil
 *
 * @package SP\Services\Upgrade
 */
class UpgradeUtil
{
    /**
     * Normalizar un número de versión
     *
     * @param $version
     * @return string
     */
    public static function fixVersionNumber($version)
    {
        if (strpos($version, '.') === false) {
            if (strlen($version) === 10) {
                return substr($version, 0, 2) . '0.' . substr($version, 2);
            }

            return substr($version, 0, 3) . '.' . substr($version, 3);
        }

        return $version;
    }

    /**
     * Establecer la key de actualización
     *
     * @param Config $config
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public static function setUpgradeKey(Config $config)
    {
        $configData = $config->getConfigData();
        $upgradeKey = $configData->getUpgradeKey();

        if (empty($upgradeKey)) {
            $configData->setUpgradeKey(Util::generateRandomBytes(32));
        }

        $configData->setMaintenance(true);
        $config->saveConfig($configData, false);

//        Init::initError(
//            __('La aplicación necesita actualizarse'),
//            sprintf(__('Si es un administrador pulse en el enlace: %s'), '<a href="index.php?a=upgrade&type=' . $type . '">' . __('Actualizar') . '</a>'));
    }

    /**
     * Comrpueba y actualiza la versión de la BBDD.
     *
     * @return void
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function checkDbVersion()
    {
        $appVersion = Util::getVersionStringNormalized();
        $databaseVersion = UserUpgrade::fixVersionNumber(ConfigDB::getValue('version'));

        if (Util::checkVersion($databaseVersion, $appVersion)
            && Request::analyze('nodbupgrade', 0) === 0
            && Util::checkVersion($databaseVersion, self::$dbUpgrade)
            && !$this->configData->isMaintenance()
        ) {
            $this->setUpgradeKey('db');

            // FIXME: send link for upgrading
            throw new \RuntimeException('Needs upgrade');
        }
    }

    /**
     * Comrpueba y actualiza la versión de la aplicación.
     *
     * @return void
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function checkAppVersion()
    {
        $appVersion = UserUpgrade::fixVersionNumber($this->configData->getConfigVersion());

        if (Util::checkVersion($appVersion, self::$appUpgrade) && !$this->configData->isMaintenance()) {
            $this->setUpgradeKey('app');

            // FIXME: send link for upgrading
            throw new \RuntimeException('Needs upgrade');
        }
    }
}