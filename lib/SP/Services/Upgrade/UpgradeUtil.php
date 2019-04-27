<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Storage\File\FileException;
use SP\Util\PasswordUtil;
use SP\Util\VersionUtil;

/**
 * Class UpgradeUtil
 *
 * @package SP\Services\Upgrade
 */
final class UpgradeUtil
{
    /**
     * Normalizar un número de versión
     *
     * @param $version
     *
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
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws FileException
     */
    public static function setUpgradeKey(Config $config)
    {
        $configData = $config->getConfigData();
        $upgradeKey = $configData->getUpgradeKey();

        if (empty($upgradeKey)) {
            $configData->setUpgradeKey(PasswordUtil::generateRandomBytes(32));
        }

        $configData->setMaintenance(true);
        $config->saveConfig($configData, false);
    }

    /**
     * @param ConfigData $configData
     * @param Config     $config
     *
     * @throws FileException
     */
    public static function fixAppUpgrade(ConfigData $configData, Config $config)
    {
        // Fixes bug in 3.0.X version where some updates weren't applied
        // when upgrading from v2
        // $dbVersion is always '' when upgrading from v2
        if (!empty($configData->getDatabaseVersion())
            && empty($configData->getAppVersion())
        ) {
            $configData->setAppVersion(VersionUtil::getVersionStringNormalized());
            $config->saveConfig($configData, false);
        }
    }
}