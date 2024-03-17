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

namespace SPT\Domain\Config\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Application;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Services\UpgradeConfig;
use SP\Domain\Core\File\MimeType;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Providers\FileLogHandlerProvider;
use SP\Infrastructure\File\FileException;
use SP\Providers\Auth\Ldap\LdapTypeEnum;
use SPT\UnitaryTestCase;

/**
 * Class UpgradeConfigTest
 *
 */
#[Group('unitary')]
class UpgradeConfigTest extends UnitaryTestCase
{

    private MimeTypesService|MockObject       $mimeTypeService;
    private FileLogHandlerProvider|MockObject $fileLogHandlerProvider;

    public static function versionDataProvider(): array
    {
        return [
            ['200.00000000', true],
            ['300.00000000', true],
            ['320.20062801', false],
            ['340.00000000', false]
        ];
    }

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testUpgrade()
    {
        $version = '200.00000000';
        $configData = $this->createMock(ConfigDataInterface::class);
        $configFileService = $this->createMock(ConfigFileService::class);
        $application = new Application(
            $configFileService,
            $this->application->getEventDispatcher(),
            $this->application->getContext()
        );

        $this->checkUpgradeV200B17011202($configData);
        $this->checkUpgradeV300B18111001($configData);
        $this->checkUpgradeLdap($configData);

        $configData->expects(self::exactly(4))
                   ->method('setConfigVersion')
                   ->with(self::anything());

        $configFileService->expects(self::exactly(4))
                          ->method('save')
                          ->with($configData, false);

        $upgradeConfig = new UpgradeConfig($application, $this->fileLogHandlerProvider, $this->mimeTypeService);
        $upgradeConfig->upgrade($version, $configData);
    }

    private function checkUpgradeV200B17011202(ConfigDataInterface|MockObject $configData): void
    {
        $configData->expects(self::once())
                   ->method('setSiteTheme')
                   ->with('material-blue');
    }

    private function checkUpgradeV300B18111001(ConfigDataInterface|MockObject $configData): void
    {
        $configData->expects(self::once())
                   ->method('getFilesAllowedExts')
                   ->willReturn(['testA', 'testB']);

        $this->mimeTypeService
            ->expects(self::exactly(2))
            ->method('getMimeTypes')
            ->willReturn([new MimeType('application/test', '', 'testA')]);

        $configData->expects(self::once())
                   ->method('setFilesAllowedMime')
                   ->with(['application/test']);
    }

    private function checkUpgradeLdap(ConfigDataInterface|MockObject $configData): void
    {
        $configData->expects(self::exactly(2))
                   ->method('isLdapEnabled')
                   ->willReturn(true);

        $configData->expects(self::once())
                   ->method('getAttributes')
                   ->willReturn(['ldapAds' => 'test']);

        $configData->expects(self::exactly(2))
                   ->method('setLdapType')
                   ->with(LdapTypeEnum::ADS->value);

        $configData->expects(self::once())
                   ->method('getLdapType')
                   ->willReturn(LdapTypeEnum::AZURE->value);
    }

    /**
     * @return void
     */
    #[DataProvider('versionDataProvider')]
    public function testNeedsUpgrade(string $version, bool $expected)
    {
        $this->assertEquals($expected, UpgradeConfig::needsUpgrade($version));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileLogHandlerProvider = $this->createMock(FileLogHandlerProvider::class);
        $this->mimeTypeService = $this->createMock(MimeTypesService::class);
    }


}
