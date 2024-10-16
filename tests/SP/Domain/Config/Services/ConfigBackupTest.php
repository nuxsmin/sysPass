<?php

declare(strict_types=1);
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

namespace SP\Tests\Domain\Config\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Config\Services\ConfigBackup;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class ConfigBackupTest
 *
 */
#[Group('unitary')]
class ConfigBackupTest extends UnitaryTestCase
{

    private ConfigService|MockObject $configService;
    private ConfigBackup $configBackup;

    public function testBackup()
    {
        $this->configService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(
                ...
                self::withConsecutive(
                    ['config_backup', self::isType('string')],
                    ['config_backup_date', self::isType('string')]
                )
            );

        $this->configBackup->backup($this->config->getConfigData());
    }

    public function testBackupError()
    {
        $this->configService
            ->expects(self::once())
            ->method('save')
            ->with('config_backup', self::anything())
            ->willThrowException(new SPException('test'));

        $this->configBackup->backup($this->config->getConfigData());
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws SPException
     */
    public function testRestore()
    {
        $configData = new ConfigData();

        $configFile = $this->createMock(ConfigFileService::class);

        $configFile->expects(self::once())
            ->method('save')
                   ->with($configData)
                   ->willReturn($configFile);

        $hexConfigData = bin2hex(gzcompress(serialize($configData)));

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willReturn($hexConfigData);

        $configFile->expects(self::once())
                   ->method('getConfigData')
                   ->willReturn($configData);

        $out = $this->configBackup->restore($configFile);

        $this->assertEquals($configData, $out);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws SPException
     */
    public function testRestoreWithNullData()
    {
        $configFile = $this->createMock(ConfigFileService::class);

        $configFile->expects(self::never())
            ->method('save');

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willReturn(null);

        $configFile->expects(self::never())
                   ->method('getConfigData');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to retrieve the configuration');

        $this->configBackup->restore($configFile);
    }

    /**
     * @throws Exception
     * @throws FileException
     * @throws SPException
     * @throws ServiceException
     */
    public function testRestoreWithMissingParam()
    {
        $configFile = $this->createMock(ConfigFileService::class);

        $configFile->expects(self::never())
            ->method('save');

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->willThrowException(new NoSuchItemException('test'));

        $configFile->expects(self::never())
                   ->method('getConfigData');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to retrieve the configuration');

        $this->configBackup->restore($configFile);
    }

    /**
     * @throws SPException
     */
    public function testConfigToJson()
    {
        $this->assertNotEmpty(ConfigBackup::configToJson(serialize($this->config->getConfigData())));
    }

    /**
     * @throws ServiceException
     */
    public function testGetBackup()
    {
        $configData = new ConfigData();

        $hexConfigData = bin2hex(gzcompress(serialize($configData)));

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willReturn($hexConfigData);

        $out = unserialize($this->configBackup->getBackup());

        $this->assertEquals($configData, $out);
    }

    public function testGetBackupWithNullData()
    {
        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to retrieve the configuration');

        $this->configBackup->getBackup();
    }

    public function testGetBackupWithMissingParam()
    {
        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->willThrowException(new NoSuchItemException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to retrieve the configuration');

        $this->configBackup->getBackup();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configService = $this->createMock(ConfigService::class);

        $this->configBackup = new ConfigBackup($this->configService);
    }
}
