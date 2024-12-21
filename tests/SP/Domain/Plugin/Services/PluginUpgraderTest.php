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

namespace SP\Tests\Domain\Plugin\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\Services\PluginUpgrader;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Tests\Generators\PluginGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class PluginUpgraderTest
 */
#[Group('unitary')]
class PluginUpgraderTest extends UnitaryTestCase
{

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpgradeForWithCompatibleVersion()
    {
        $pluginModel = PluginGenerator::factory()->buildPlugin();

        $pluginManagerService = $this->createMock(PluginManagerService::class);

        $pluginUpgrader = new PluginUpgrader($this->application, $pluginManagerService);

        $plugin = self::createMock(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService->expects($this->once())
                             ->method('getByName')
                             ->willReturn($pluginModel);

        [$version, $build] = explode('.', $pluginModel->getVersionLevel());

        $upgradeVersion = sprintf('%d.%d', (int)$version + 100, (int)$build);

        $plugin->expects($this->once())
               ->method('onUpgrade')
            ->with($upgradeVersion);

        $pluginManagerService->expects($this->once())
                             ->method('update')
            ->with($pluginModel->mutate(['data' => null, 'versionLevel' => $upgradeVersion]));

        $pluginUpgrader->upgradeFor($plugin, $upgradeVersion);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpgradeForWithNoCompatibleVersion()
    {
        $pluginModel = PluginGenerator::factory()->buildPlugin();

        $pluginManagerService = $this->createMock(PluginManagerService::class);

        $pluginUpgrader = new PluginUpgrader($this->application, $pluginManagerService);

        $plugin = self::createMock(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService->expects($this->once())
                             ->method('getByName')
                             ->willReturn($pluginModel);

        [$version, $build] = explode('.', $pluginModel->getVersionLevel());

        $upgradeVersion = sprintf('%d.%d', (int)$version - 100, (int)$build);

        $plugin->expects($this->never())
               ->method('onUpgrade');

        $pluginManagerService->expects($this->never())
                             ->method('update');

        $pluginUpgrader->upgradeFor($plugin, $upgradeVersion);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpgradeForWithNullVersion()
    {
        $pluginModel = PluginGenerator::factory()->buildPlugin()->mutate(['versionLevel' => null]);

        $pluginManagerService = $this->createMock(PluginManagerService::class);

        $pluginUpgrader = new PluginUpgrader($this->application, $pluginManagerService);

        $plugin = self::createMock(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService->expects($this->once())
                             ->method('getByName')
                             ->willReturn($pluginModel);

        $plugin->expects($this->once())
               ->method('onUpgrade')
               ->with('1.1.1');

        $pluginManagerService->expects($this->once())
                             ->method('update')
                             ->with($pluginModel->mutate(['data' => null, 'versionLevel' => '1.1.1']));

        $pluginUpgrader->upgradeFor($plugin, '1.1.1');
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpgradeForWithException()
    {
        $pluginManagerService = $this->createMock(PluginManagerService::class);

        $pluginUpgrader = new PluginUpgrader($this->application, $pluginManagerService);

        $plugin = self::createMock(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService->expects($this->once())
                             ->method('getByName')
                             ->willThrowException(NoSuchItemException::error('test'));

        $plugin->expects($this->never())
               ->method('onUpgrade');

        $pluginManagerService->expects($this->never())
                             ->method('update');

        $pluginUpgrader->upgradeFor($plugin, '1.1.1');
    }
}
