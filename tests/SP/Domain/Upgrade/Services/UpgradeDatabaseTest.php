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

namespace SP\Tests\Domain\Upgrade\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Services\UpgradeDatabase;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class UpgradeDatabaseTest
 */
#[Group('unitary')]
class UpgradeDatabaseTest extends UnitaryTestCase
{
    /**
     * @throws Exception
     * @throws UpgradeException
     * @throws FileException
     */
    public function testUpgrade()
    {
        $fileHandlerProvider = $this->createMock(FileHandlerProvider::class);
        $database = $this->createMock(DatabaseInterface::class);
        $configData = $this->createMock(ConfigDataInterface::class);

        $database->expects($this->exactly(2))
                 ->method('runQueryRaw')
                 ->with(
                     ...
                     self::withConsecutive(
                         ['alter table CustomFieldData drop column id'],
                         ['alter table CustomFieldData add primary key (moduleId, itemId, definitionId)']
                     )
                 );

        $configData->expects($this->once())
                   ->method('setDatabaseVersion')
                   ->with('400.24210101');

        $this->config->expects($this->once())
                     ->method('save')
                     ->with($configData);

        $upgradeDatabase = new UpgradeDatabase($this->application, $fileHandlerProvider, $database);
        $upgradeDatabase->upgrade('400.00000000', $configData);
    }

    /**
     * @throws Exception
     * @throws UpgradeException
     * @throws FileException
     */
    public function testUpgradeWithException()
    {
        $fileHandlerProvider = $this->createMock(FileHandlerProvider::class);
        $database = $this->createMock(DatabaseInterface::class);
        $configData = $this->createMock(ConfigDataInterface::class);

        $database->expects($this->once())
                 ->method('runQueryRaw')
                 ->willThrowException(new RuntimeException('test'));

        $configData->expects($this->never())
                   ->method('setDatabaseVersion');

        $upgradeDatabase = new UpgradeDatabase($this->application, $fileHandlerProvider, $database);

        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Error while updating the database');

        $upgradeDatabase->upgrade('400.00000000', $configData);
    }

    /**
     * @throws Exception
     * @throws UpgradeException
     * @throws FileException
     */
    public function testUpgradeWithNoUpgrades()
    {
        $fileHandlerProvider = $this->createMock(FileHandlerProvider::class);
        $database = $this->createMock(DatabaseInterface::class);
        $configData = $this->createMock(ConfigDataInterface::class);

        $database->expects($this->never())
                 ->method('runQueryRaw');

        $configData->expects($this->never())
                   ->method('setDatabaseVersion');

        $upgradeDatabase = new UpgradeDatabase($this->application, $fileHandlerProvider, $database);
        $upgradeDatabase->upgrade('400.24210101', $configData);
    }
}
