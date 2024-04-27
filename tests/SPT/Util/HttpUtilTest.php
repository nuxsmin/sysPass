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

namespace SPT\Util;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Http\RequestInterface;
use SP\Util\HttpUtil;

/**
 * Class HttpUtilTest
 */
#[Group('unitary')]
class HttpUtilTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testCheckHttps()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $configData->expects($this->once())
                   ->method('isHttpsEnabled')
                   ->willReturn(true);

        $request->expects($this->once())
                ->method('isHttps')
                ->willReturn(false);

        $request->expects($this->once())
                ->method('getServerPort')
                ->willReturn(8080);

        $request->expects($this->once())
                ->method('getHttpHost')
                ->willReturn('localhost');

        HttpUtil::checkHttps($configData, $request);
    }

    /**
     * @throws Exception
     */
    public function testCheckHttpsWithNoHttpsEnabled()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $configData->expects($this->once())
                   ->method('isHttpsEnabled')
                   ->willReturn(false);

        $request->expects($this->never())
                ->method('isHttps');

        $request->expects($this->never())
                ->method('getServerPort');

        $request->expects($this->never())
                ->method('getHttpHost');

        HttpUtil::checkHttps($configData, $request);
    }

    /**
     * @throws Exception
     */
    public function testCheckHttpsWithHttpsEnabledAndHttpsRequest()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $configData->expects($this->once())
                   ->method('isHttpsEnabled')
                   ->willReturn(true);

        $request->expects($this->once())
                ->method('isHttps')
                ->willReturn(true);

        $request->expects($this->never())
                ->method('getServerPort');

        $request->expects($this->never())
                ->method('getHttpHost');

        HttpUtil::checkHttps($configData, $request);
    }
}
