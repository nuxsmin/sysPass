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
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\Services\PluginCompatility;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SPT\UnitaryTestCase;

/**
 * Class PluginCompatilityTest
 */
#[Group('unitary')]
class PluginCompatilityTest extends UnitaryTestCase
{

    private MockObject|PluginManagerService $pluginManagerService;
    private PluginCompatility               $pluginCompatibility;

    /**
     * @throws NoSuchItemException
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckForWithCompatible()
    {
        $plugin = $this->createMock(Plugin::class);

        $plugin->expects($this->once())
               ->method('getCompatibleVersion')
               ->willReturn([4, 0, 0]);

        $plugin->method('getVersion')
               ->willReturn([2, 1, 0]);

        $plugin->method('getName')
               ->willReturn('test_plugin');

        $this->assertTrue($this->pluginCompatibility->checkFor($plugin));
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCheckForWithNoCompatible()
    {
        $plugin = $this->createMock(Plugin::class);

        $plugin->expects($this->once())
               ->method('getCompatibleVersion')
               ->willReturn([3, 0, 0]);

        $plugin->method('getVersion')
               ->willReturn([2, 1, 0]);

        $plugin->method('getName')
               ->willReturn('test_plugin');

        $this->pluginManagerService
            ->expects($this->once())
            ->method('toggleEnabledByName')
            ->with('test_plugin', false);

        $this->assertFalse($this->pluginCompatibility->checkFor($plugin));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginManagerService = $this->createMock(PluginManagerService::class);

        $this->pluginCompatibility = new PluginCompatility($this->application, $this->pluginManagerService);
    }
}
