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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigBackupService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFile;
use SP\Domain\Core\Exceptions\ConfigException;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Storage\Ports\XmlFileStorageService;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class ConfigFileTest
 *
 */
#[Group('unitary')]
class ConfigFileTest extends UnitaryTestCase
{
    private XmlFileStorageService|MockObject $fileStorageService;
    private FileCacheService|MockObject      $fileCacheService;
    private ConfigBackupService|MockObject   $configBackupService;

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithProvided()
    {
        $this->fileCacheService
            ->expects(self::never())
            ->method('exists');

        $this->fileStorageService
            ->expects(self::never())
            ->method('getFileTime');

        $this->fileStorageService
            ->expects(self::never())
            ->method('save');

        $this->fileCacheService
            ->expects(self::never())
            ->method('save');

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService,
            new ConfigData()
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithCache()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $time = time();

        $this->fileStorageService
            ->expects(self::once())
            ->method('getFileTime')
            ->willReturn($time);

        $this->fileCacheService
            ->expects(self::once())
            ->method('isExpiredDate')
            ->with($time)
            ->willReturn(false);

        $this->fileCacheService
            ->expects(self::once())
            ->method('loadWith')
            ->with(ConfigData::class)
            ->willReturn($this->config->getConfigData());

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithCacheAndNoAttributes()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $time = time();

        $this->fileStorageService
            ->expects(self::once())
            ->method('getFileTime')
            ->willReturn($time);

        $this->fileCacheService
            ->expects(self::once())
            ->method('isExpiredDate')
            ->with($time)
            ->willReturn(false);

        $this->fileCacheService
            ->expects(self::once())
            ->method('loadWith')
            ->with(ConfigData::class)
            ->willReturn(new ConfigData());

        $this->fileCacheService
            ->expects(self::once())
            ->method('delete');

        $this->ensureConfigFileIsUsed();

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @return void
     */
    private function ensureConfigFileIsUsed(): void
    {
        $configData = $this->config->getConfigData();

        $this->fileStorageService
            ->expects(self::once())
            ->method('load')
            ->with('config')
            ->willReturn($configData->getAttributes());

        $this->fileCacheService
            ->expects(self::once())
            ->method('save')
            ->with($configData);
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithExistingFile()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->ensureConfigFileIsUsed();

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithGenerateNewConfig()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->fileStorageService
            ->expects(self::never())
            ->method('getFileTime');

        $this->fileStorageService
            ->expects(self::once())
            ->method('load')
            ->with('config')
            ->willThrowException(FileException::error('test'));

        $this->configBackupService
            ->expects(self::never())
            ->method('backup');

        $this->fileStorageService
            ->expects(self::once())
            ->method('save')
            ->with(self::isType('array'), 'config');

        $this->fileCacheService
            ->expects(self::once())
            ->method('save')
            ->with(self::anything());

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithExceptionFromCache()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $time = time();

        $this->fileStorageService
            ->expects(self::once())
            ->method('getFileTime')
            ->willReturn($time);

        $this->fileCacheService
            ->expects(self::once())
            ->method('isExpiredDate')
            ->with($time)
            ->willReturn(false);

        $this->fileCacheService
            ->expects(self::once())
            ->method('loadWith')
            ->with(ConfigData::class)
            ->willThrowException(FileException::error('test'));

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('test');

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithExceptionFromCacheExpired()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $time = time();

        $this->fileStorageService
            ->expects(self::exactly(1))
            ->method('getFileTime')
            ->willReturn($time);

        $this->fileCacheService
            ->expects(self::once())
            ->method('isExpiredDate')
            ->with($time)
            ->willThrowException(FileException::error('test'));

        $this->fileCacheService
            ->expects(self::never())
            ->method('loadWith');

        $this->ensureConfigFileIsUsed();

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testInitializeWithExceptionCacheDelete()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(true);

        $time = time();

        $this->fileStorageService
            ->expects(self::once())
            ->method('getFileTime')
            ->willReturn($time);

        $this->fileCacheService
            ->expects(self::once())
            ->method('isExpiredDate')
            ->with($time)
            ->willReturn(false);

        $this->fileCacheService
            ->expects(self::once())
            ->method('loadWith')
            ->with(ConfigData::class)
            ->willReturn(new ConfigData());

        $this->fileCacheService
            ->expects(self::once())
            ->method('delete')
            ->willThrowException(FileException::error('test'));

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('test');

        new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );
    }

    /**
     * @throws ConfigException
     * @throws Exception
     * @throws FileException
     */
    public function testSave()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->fileStorageService
            ->expects(self::never())
            ->method('getFileTime');

        $this->fileStorageService
            ->expects(self::once())
            ->method('load')
            ->willThrowException(FileException::error('test'));

        $configData = $this->createMock(ConfigDataInterface::class);

        $this->configBackupService
            ->expects(self::once())
            ->method('backup')
            ->with($configData);

        $this->fileStorageService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::isType('array'), 'config');

        $this->fileCacheService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::anything());

        $configFile = new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );

        $configData->expects(self::once())
                   ->method('setConfigDate');
        $configData->expects(self::once())
                   ->method('setConfigSaver');
        $configData->expects(self::once())
                   ->method('setConfigHash');
        $configData->expects(self::once())
                   ->method('getAttributes')
                   ->willReturn([]);

        $configFile->save($configData);

        $this->assertEquals($configData, $configFile->getConfigData());
    }

    /**
     * @throws ConfigException
     * @throws Exception
     * @throws FileException
     */
    public function testSaveWithoutBackup()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->fileStorageService
            ->expects(self::once())
            ->method('load')
            ->willThrowException(FileException::error('test'));

        $configData = $this->createMock(ConfigDataInterface::class);

        $this->configBackupService
            ->expects(self::never())
            ->method('backup');

        $this->fileStorageService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::isType('array'), 'config');

        $this->fileCacheService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::anything());

        $configFile = new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );

        $configData->expects(self::once())
                   ->method('setConfigDate');
        $configData->expects(self::once())
                   ->method('setConfigSaver');
        $configData->expects(self::once())
                   ->method('setConfigHash');
        $configData->expects(self::once())
                   ->method('getAttributes')
                   ->willReturn([]);

        $configFile->save($configData, false, true);

        $this->assertEquals($configData, $configFile->getConfigData());
    }

    /**
     * @throws ConfigException
     * @throws Exception
     * @throws FileException
     */
    public function testSaveWithoutCommit()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->fileStorageService
            ->expects(self::once())
            ->method('load')
            ->willThrowException(FileException::error('test'));

        $configData = $this->createMock(ConfigDataInterface::class);

        $this->configBackupService
            ->expects(self::never())
            ->method('backup');

        $this->fileStorageService
            ->expects(self::exactly(1))
            ->method('save')
            ->with(self::isType('array'), 'config');

        $this->fileCacheService
            ->expects(self::exactly(1))
            ->method('save')
            ->with(self::anything());

        $configFile = new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );

        $configData->expects(self::once())
                   ->method('setConfigDate');
        $configData->expects(self::once())
                   ->method('setConfigSaver');
        $configData->expects(self::once())
                   ->method('setConfigHash');
        $configData->expects(self::never())
                   ->method('getAttributes');

        $configFile->save($configData, false, false);

        $this->assertEquals($configData, $configFile->getConfigData());
    }

    /**
     * @throws ConfigException
     * @throws Exception
     * @throws FileException
     * @throws EnvironmentIsBrokenException
     */
    public function testGenerateUpgradeKey()
    {
        $this->fileCacheService
            ->expects(self::once())
            ->method('exists')
            ->willReturn(false);

        $this->fileStorageService
            ->expects(self::once())
            ->method('load')
            ->willThrowException(FileException::error('test'));

        $this->configBackupService
            ->expects(self::never())
            ->method('backup');

        $this->fileStorageService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::isType('array'), 'config');

        $this->fileCacheService
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::anything());

        $configFile = new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );

        $configFile->generateUpgradeKey();

        $this->assertNotEmpty($configFile->getConfigData()->getUpgradeKey());
    }

    /**
     * @throws ConfigException
     * @throws Exception
     * @throws FileException
     * @throws EnvironmentIsBrokenException
     */
    public function testGenerateUpgradeKeyWithExistingKey()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $configData->expects(self::once())
                   ->method('getUpgradeKey')
                   ->willReturn(self::$faker->sha1());

        $configData->expects(self::never())
                   ->method('setUpgradeKey');

        $configFile = new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService,
            $configData
        );

        $configFile->generateUpgradeKey();
    }

    /**
     * @throws ConfigException
     * @throws Exception
     */
    public function testReload()
    {
        $this->fileCacheService
            ->expects(self::exactly(2))
            ->method('exists')
            ->willReturn(false);

        $this->fileStorageService
            ->expects(self::never())
            ->method('getFileTime');

        $configData = $this->config->getConfigData();

        $this->fileStorageService
            ->expects(self::exactly(2))
            ->method('load')
            ->with('config')
            ->willReturn($configData->getAttributes());

        $this->fileCacheService
            ->expects(self::exactly(2))
            ->method('save')
            ->with($configData);

        $configFile = new ConfigFile(
            $this->fileStorageService,
            $this->fileCacheService,
            $this->context,
            $this->configBackupService
        );

        $configFile->reload();
    }

    /**
     * @throws Exception
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileStorageService = $this->createMock(XmlFileStorageService::class);
        $this->fileCacheService = $this->createMock(FileCacheService::class);
        $this->configBackupService = $this->createMock(ConfigBackupService::class);
    }
}
