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
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Upgrade\Services\UpgradeDatabase;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Tests\UnitaryTestCase;

/**
 * Class UpgradeDatabaseTest
 */
#[Group('unitary')]
class UpgradeDatabaseTest extends UnitaryTestCase
{
    private UpgradeDatabase $upgradeDatabase;
    private DatabaseInterface|MockObject $database;

    /**
     * @throws Exception
     * @throws UpgradeException
     */
    public function testUpgrade()
    {
        $configData = $this->createMock(ConfigDataInterface::class);

        $this->database->expects($this->exactly(2))
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

        $this->upgradeDatabase->apply('400.24210101', $configData);
    }

    /**
     * @throws Exception
     * @throws UpgradeException
     */
    public function testUpgradeWithException()
    {
        $configData = $this->createMock(ConfigDataInterface::class);

        $this->database->expects($this->once())
                       ->method('runQueryRaw')
                       ->willThrowException(new RuntimeException('test'));

        $configData->expects($this->never())
                   ->method('setDatabaseVersion');

        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Error while updating the database');

        $this->upgradeDatabase->apply('400.24210101', $configData);
    }

    /**
     * @throws Exception
     * @throws UpgradeException
     */
    public function testUpgradeWithFileException()
    {
        $configData = $this->createMock(ConfigDataInterface::class);

        $this->database->expects($this->never())
                       ->method('runQueryRaw');

        $configData->expects($this->never())
                   ->method('setDatabaseVersion');

        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Failed to open stream: No such file or directory');

        $this->upgradeDatabase->apply('400.00000000', $configData);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseInterface::class);
        $this->upgradeDatabase = new UpgradeDatabase($this->application, $this->database);
    }
}
