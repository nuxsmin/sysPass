<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\Upgrade\Services;

use SP\Domain\Common\Providers\Version;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Infrastructure\File\FileException;

/**
 * Class UpgradeUtil
 */
final class UpgradeUtil
{
    /**
     * Normalizar un número de versión
     */
    public static function fixVersionNumber(string $version): string
    {
        if (!str_contains($version, '.')) {
            if (strlen($version) === 10) {
                return substr($version, 0, 2) . '0.' . substr($version, 2);
            }

            return substr($version, 0, 3) . '.' . substr($version, 3);
        }

        return $version;
    }

    /**
     * @throws FileException
     */
    public static function fixAppUpgrade(ConfigDataInterface $configData, ConfigFileService $config): void
    {
        // Fixes bug in 3.0.X version where some updates weren't applied
        // when upgrading from v2
        // $dbVersion is always '' when upgrading from v2
        if (!empty($configData->getDatabaseVersion()) && empty($configData->getAppVersion())) {
            $configData->setAppVersion(Version::getVersionStringNormalized());
            $config->save($configData, false);
        }
    }
}
