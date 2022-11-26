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

namespace SP\Domain\Config\Ports;

use SP\Domain\Upgrade\Services\UpgradeInterface;

/**
 * Class UpgradeService
 *
 * @package SP\Domain\Upgrade\Services
 */
interface UpgradeConfigServiceInterface extends UpgradeInterface
{
    /**
     * Actualizar el archivo de configuración a formato XML
     *
     * @throws \SP\Domain\Upgrade\Services\UpgradeException
     */
    public function upgradeOldConfigFile(string $version): void;
}
