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

namespace SP\Domain\Config\Services;

use SP\Core\Application;
use SP\Domain\Config\Ports\UpgradeConfigService;
use SP\Domain\Providers\Ports\FileLogHandlerProvider;
use SP\Domain\Upgrade\Services\UpgradeBase;

/**
 * Class UpgradeConfig
 */
final class UpgradeConfig extends UpgradeBase implements UpgradeConfigService
{
    public function __construct(
        Application            $application,
        FileLogHandlerProvider $fileLogHandlerProvider
    ) {
        parent::__construct($application, $fileLogHandlerProvider);

        $this->eventDispatcher->attach($fileLogHandlerProvider);
    }


    protected static function getUpgrades(): array
    {
        return [];
    }

    protected function commitVersion(string $version): void
    {
        $this->configData->setConfigVersion($version);
    }

    protected function applyUpgrade(string $version): bool
    {
        return true;
    }
}
