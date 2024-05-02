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

declare(strict_types=1);

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2024 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Tests\Domain\Upgrade\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Services\UpgradeApp;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class UpgradeAppTest
 */
#[Group('unitary')]
class UpgradeAppTest extends UnitaryTestCase
{
    /**
     * @throws Exception
     * @throws UpgradeException
     * @throws FileException
     */
    public function testUpgrade()
    {
        $fileHandlerProvider = $this->createMock(FileHandlerProvider::class);
        $configData = $this->createMock(ConfigDataInterface::class);

        $configData->expects($this->never())
                   ->method('setAppVersion');

        $this->config->expects($this->never())
                     ->method('save');

        $upgradeApp = new UpgradeApp($this->application, $fileHandlerProvider);
        $upgradeApp->upgrade('123', $configData);
    }
}
