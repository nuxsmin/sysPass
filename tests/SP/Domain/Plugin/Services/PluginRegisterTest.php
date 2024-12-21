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
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\Services\PluginRegister;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Tests\UnitaryTestCase;

/**
 * Class PluginRegisterTest
 */
#[Group('unitary')]
class PluginRegisterTest extends UnitaryTestCase
{

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRegisterForWithUnregisteredPlugin()
    {
        $plugin = $this->createStub(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService = $this->createMock(PluginManagerService::class);
        $pluginManagerService->expects($this->once())
                             ->method('getByName')
                             ->with('test_plugin')
                             ->willThrowException(NoSuchItemException::error('test'));

        $pluginManagerService->expects($this->once())
                             ->method('create')
            ->with(new PluginModel(['name' => 'test_plugin', 'enabled' => false]));

        $pluginRegister = new PluginRegister($this->application, $pluginManagerService);

        $pluginRegister->registerFor($plugin);
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    public function testRegisterForWithRegisteredPlugin()
    {
        $plugin = $this->createStub(Plugin::class);
        $plugin->method('getName')->willReturn('test_plugin');

        $pluginManagerService = $this->createMock(PluginManagerService::class);
        $pluginManagerService->expects($this->once())
                             ->method('getByName')
                             ->with('test_plugin');

        $pluginManagerService->expects($this->never())
                             ->method('create');

        $pluginRegister = new PluginRegister($this->application, $pluginManagerService);

        $pluginRegister->registerFor($plugin);
    }
}
