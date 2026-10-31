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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Config\Services\ConfigBackup;
use SP\Domain\Core\Exceptions\FileException;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Log\LoggerInterface;
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

    #[Test]
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

    #[Test]
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
    #[Test]
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

        $this->configService->expects(self::never())
                            ->method('save');

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
    #[Test]
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
    #[Test]
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
    #[Test]
    public function testConfigToJson()
    {
        $this->configService->expects(self::never())
                            ->method('save');

        $this->assertNotEmpty($this->configBackup->configToJson(serialize($this->config->getConfigData())));
    }

    /**
     * @throws ServiceException
     */
    #[Test]
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

    #[Test]
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

    #[Test]
    public function testGetBackupWithMissingParam()
    {
        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willThrowException(new NoSuchItemException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to retrieve the configuration');

        $this->configBackup->getBackup();
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws SPException
     */
    #[Test]
    public function testRestoreLegacyConfigData()
    {
        $configFile = $this->createMock(ConfigFileService::class);

        $configFile->expects(self::once())
                   ->method('save')
                   ->with(self::callback(static function (ConfigData $configData): bool {
                       return $configData->getDbHost() === 'syspass-db-dev'
                              && $configData->getDbPort() === 3306
                              && $configData->getFilesAllowedExts() === [
                                  'PDF',
                                  'JPG',
                                  'GIF',
                                  'PNG',
                                  'ODT',
                                  'ODS',
                                  'DOC',
                                  'DOCX',
                                  'XLS',
                                  'XSL',
                                  'VSD',
                                  'TXT',
                                  'CSV',
                                  'BAK',
                                  'RDP',
                                  'KEY',
                                  'CRT',
                                  'XML',
                              ]
                              && $configData->getBackupHash() === 'c9142988b9d91a375ca83387f0593c7a12eaf5a2';
                   }))
                   ->willReturn($configFile);

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willReturn(self::legacyBackupRow());

        $this->configService->expects(self::never())
                            ->method('save');

        $configFile->expects(self::once())
                   ->method('getConfigData')
                   ->willReturnCallback(static fn() => new ConfigData());

        $this->configBackup->restore($configFile);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function testRestoreWithUnreadableBackupDoesNotModifyTheConfig()
    {
        $configFile = $this->createMock(ConfigFileService::class);

        $configFile->expects(self::never())
                   ->method('save');

        $configFile->expects(self::never())
                   ->method('getConfigData');

        $this->configService->expects(self::once())
                            ->method('getByParam')
                            ->with('config_backup')
                            ->willReturn(bin2hex(gzcompress('not-a-serialized-payload')));

        $this->configService->expects(self::never())
                            ->method('save');

        $this->expectException(InvalidClassException::class);

        $this->configBackup->restore($configFile);
    }

    /**
     * @throws SPException
     */
    #[Test]
    public function testConfigToJsonWithLegacyConfigData()
    {
        $this->configService->expects(self::never())
                            ->method('save');

        $json = $this->configBackup->configToJson(self::legacyConfigPayload());

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('syspass-db-dev', $decoded['dbHost']);
        self::assertSame(3306, $decoded['dbPort']);
        self::assertSame('c9142988b9d91a375ca83387f0593c7a12eaf5a2', $decoded['backup_hash']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function testLegacyFieldsAreLoggedWithoutPayloadValues()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $messages = [];

        $logger->expects(self::exactly(2))
               ->method('warning')
               ->willReturnCallback(static function (string $message) use (&$messages): void {
                   $messages[] = $message;
               });

        $configBackup = new ConfigBackup($this->configService, $logger);

        $configBackup->configToJson(self::legacyConfigPayload());

        $logged = implode("\n", $messages);

        self::assertStringContainsString('ldapAds', $logged);
        self::assertStringContainsString('ldapProxyUser', $logged);
        self::assertStringContainsString('ldapUserattr', $logged);
        self::assertStringNotContainsString('syspass-db-dev', $logged);
    }

    private static function legacyBackupRow(): string
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/res/datasets/syspass.xml');

        self::assertNotFalse($xml);

        foreach ($xml->xpath('//table_data[@name="Config"]/row') as $row) {
            if ((string)$row->field[0] === 'config_backup') {
                return (string)$row->field[1];
            }
        }

        self::fail('config_backup row not found');
    }

    private static function legacyConfigPayload(): string
    {
        return (string)gzuncompress((string)hex2bin(self::legacyBackupRow()));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configService = $this->createMock(ConfigService::class);

        $this->configBackup = new ConfigBackup($this->configService);
    }
}
