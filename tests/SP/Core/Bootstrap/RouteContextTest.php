<?php
/**
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

namespace SP\Tests\Core\Bootstrap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SP\Core\Bootstrap\RouteContext;

/**
 * Class RouteContextTest
 */
#[Group('unitary')]
class RouteContextTest extends TestCase
{
    public static function wrongRouteProvider(): array
    {
        return [
            ['testController_1'],
            ['testController/action_1']
        ];
    }

    public function testGetRouteContextData()
    {
        $route = 'testController/testAction/param1/param2';

        $out = RouteContext::getRouteContextData($route);

        $this->assertEquals('testController', $out->getController());
        $this->assertEquals('testAction', $out->getActionName());
        $this->assertEquals('testActionAction', $out->getMethodName());
        $this->assertEquals('param1', $out->getMethodParams()[0]);
        $this->assertEquals('param2', $out->getMethodParams()[1]);
    }

    public function testGetRouteContextDataWithNoaction()
    {
        $route = 'testController';

        $out = RouteContext::getRouteContextData($route);

        $this->assertEquals('testController', $out->getController());
        $this->assertEquals('index', $out->getActionName());
        $this->assertEquals('indexAction', $out->getMethodName());
        $this->assertCount(0, $out->getMethodParams());
    }

    public function testGetRouteContextDataWithActionAndNoParam()
    {
        $route = 'testController/testAction';

        $out = RouteContext::getRouteContextData($route);

        $this->assertEquals('testController', $out->getController());
        $this->assertEquals('testAction', $out->getActionName());
        $this->assertEquals('testActionAction', $out->getMethodName());
        $this->assertCount(0, $out->getMethodParams());
    }

    #[DataProvider('wrongRouteProvider')]
    public function testGetRouteContextDataWithException(string $route)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid route');

        RouteContext::getRouteContextData($route);
    }
}
