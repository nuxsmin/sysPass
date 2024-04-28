<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Core\Bootstrap;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Core\Bootstrap\UriContext;
use SP\Domain\Http\Ports\RequestService;
use SPT\UnitaryTestCase;

/**
 * Class UriContextTest
 */
#[Group('unitary')]
class UriContextTest extends UnitaryTestCase
{
    /**
     * @throws Exception
     */
    public function testConstruct()
    {
        $request = $this->createMock(RequestService::class);
        $request->expects(self::exactly(2))
                ->method('getServer')
                ->with(...$this->withConsecutive(['SCRIPT_FILENAME'], ['REQUEST_URI']))
                ->willReturn('/some/path/to/test.php', '/syspass/test.php');

        $domainName = self::$faker->domainName;

        $request->expects(self::once())
                ->method('getHttpHost')
                ->willReturn($domainName);

        $uriContext = new UriContext($request);

        $this->assertEquals('/test.php', $uriContext->getSubUri());
        $this->assertEquals('/syspass', $uriContext->getWebRoot());
        $this->assertEquals($domainName . '/syspass', $uriContext->getWebUri());
    }

    /**
     * @throws Exception
     */
    public function testConstructWithoutWebRoot()
    {
        $request = $this->createMock(RequestService::class);
        $request->expects(self::exactly(2))
                ->method('getServer')
                ->with(...$this->withConsecutive(['SCRIPT_FILENAME'], ['REQUEST_URI']))
                ->willReturn('/some/path/to/test.php', 'test.php');

        $domainName = self::$faker->domainName;

        $request->expects(self::once())
                ->method('getHttpHost')
                ->willReturn($domainName);

        $uriContext = new UriContext($request);

        $this->assertEquals('/test.php', $uriContext->getSubUri());
        $this->assertEquals('', $uriContext->getWebRoot());
        $this->assertEquals($domainName, $uriContext->getWebUri());
    }

}
