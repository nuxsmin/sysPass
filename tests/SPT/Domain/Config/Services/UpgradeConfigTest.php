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
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;

/**
 * Class UpgradeConfigTest
 *
 */
#[Group('unitary')]
class UpgradeConfigTest extends UnitaryTestCase
{
    private FileHandlerProvider|MockObject $fileLogHandlerProvider;

    public static function versionDataProvider(): array
    {
        return [
            ['320.20062801', false],
            ['340.00000000', false]
        ];
    }

    /**
     * @throws Exception
     * @throws FileException
     * @throws UpgradeException
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

        $configData->expects(self::never())
                   ->method('setConfigVersion')
                   ->with(self::anything());

        $configFileService->expects(self::never())
                          ->method('save')
                          ->with($configData, false);

        $upgradeConfig = new UpgradeConfig($application, $this->fileLogHandlerProvider);
        $upgradeConfig->upgrade($version, $configData);
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

        $this->fileLogHandlerProvider = $this->createMock(FileHandlerProvider::class);
    }
}
