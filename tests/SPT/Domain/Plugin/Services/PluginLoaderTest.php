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

namespace SPT\Domain\Plugin\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Models\Plugin;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\Services\PluginLoader;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SPT\UnitaryTestCase;

/**
 * Class PluginLoaderTest
 */
#[Group('unitary')]
class PluginLoaderTest extends UnitaryTestCase
{
    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testLoadForWithEnabled()
    {
        $pluginManagerService = $this->createMock(PluginManagerService::class);
        $plugin = $this->createStub(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginModel = new Plugin(['enabled' => true]);

        $pluginManagerService
            ->expects($this->once())
            ->method('getByName')
            ->with('test_plugin')
            ->willReturn($pluginModel);

        $pluginLoader = new PluginLoader($this->application, $pluginManagerService);
        $pluginLoader->loadFor($plugin);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testLoadForWithDisabled()
    {
        $pluginManagerService = $this->createMock(PluginManagerService::class);
        $plugin = $this->createStub(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginModel = new Plugin(['enabled' => false]);

        $pluginManagerService
            ->expects($this->once())
            ->method('getByName')
            ->with('test_plugin')
            ->willReturn($pluginModel);

        $pluginLoader = new PluginLoader($this->application, $pluginManagerService);
        $pluginLoader->loadFor($plugin);
    }

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testLoadForWithException()
    {
        $pluginManagerService = $this->createMock(PluginManagerService::class);
        $plugin = $this->createStub(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService
            ->expects($this->once())
            ->method('getByName')
            ->willThrowException(NoSuchItemException::error('test'));

        $pluginLoader = new PluginLoader($this->application, $pluginManagerService);
        $pluginLoader->loadFor($plugin);
    }
}
