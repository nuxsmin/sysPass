<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Bootstrap;


use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\UpgradeConfigServiceInterface;
use SP\Domain\Config\Services\UpgradeConfigService;
use SP\Domain\Upgrade\Services\UpgradeUtil;
use SP\Util\VersionUtil;

/**
 * Upgrade the config whenever is needed
 */
class UpgradeConfigChecker
{
    private UpgradeConfigService $upgradeConfigService;
    private ConfigDataInterface  $configData;

    public function __construct(UpgradeConfigServiceInterface $upgradeConfigService, ConfigDataInterface $configData)
    {
        $this->upgradeConfigService = $upgradeConfigService;
        $this->configData = $configData;
    }

    /**
     * Comprobar la versión de configuración y actualizarla
     *
     * @throws \SP\Domain\Upgrade\Services\UpgradeException
     */
    public function checkConfigVersion(): void
    {
        // TODO: remove
        // Do not check config version when testing
        if (IS_TESTING) {
            return;
        }

        if (defined('OLD_CONFIG_FILE')
            && file_exists(OLD_CONFIG_FILE)) {
            $this->upgradeConfigService->upgradeOldConfigFile(VersionUtil::getVersionStringNormalized());
        }

        $configVersion = UpgradeUtil::fixVersionNumber($this->configData->getConfigVersion());

        if ($this->configData->isInstalled()
            && UpgradeConfigService::needsUpgrade($configVersion)
        ) {
            $this->upgradeConfigService->upgrade($configVersion, $this->configData);
        }
    }
}
