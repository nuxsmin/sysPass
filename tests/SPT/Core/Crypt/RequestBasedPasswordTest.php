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

namespace SPT\Core\Crypt;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Core\Crypt\RequestBasedPassword;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Http\RequestInterface;
use SPT\UnitaryTestCase;

/**
 * Class RequestBasedPasswordTest
 */
#[Group('unitary')]
class RequestBasedPasswordTest extends UnitaryTestCase
{

    /**
     * @throws Exception
     */
    public function testBuild()
    {
        $request = $this->createMock(RequestInterface::class);
        $configData = $this->createMock(ConfigDataInterface::class);

        $request->expects($this->once())
                ->method('getHeader')
                ->with('User-Agent')
                ->willReturn(self::$faker->userAgent());

        $request->expects($this->once())
                ->method('getClientAddress')
                ->willReturn(self::$faker->ipv4());

        $configData->expects($this->once())
                   ->method('getPasswordSalt')
                   ->willReturn(self::$faker->sha1());

        $requestBasedPassword = new RequestBasedPassword($request, $configData);
        $this->assertNotEmpty($requestBasedPassword->build());
    }
}
