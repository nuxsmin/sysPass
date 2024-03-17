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

namespace SPT\Core\Crypt;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Crypt\Session;
use SP\Domain\Core\Context\SessionContextInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SPT\UnitaryTestCase;

/**
 * Class SessionTest
 *
 */
#[Group('unitary')]
class SessionTest extends UnitaryTestCase
{

    private SessionContextInterface|MockObject $sessionContext;

    /**
     * @throws Exception
     * @throws CryptException
     */
    public function testGetSessionKey()
    {
        $sidStartTime = self::$faker->unixTime;

        $key = session_id() . $sidStartTime;

        $vault = $this->createMock(VaultInterface::class);
        $vault->expects(self::once())
              ->method('getData')
              ->with($key)
              ->willReturn('data');

        $this->sessionContext
            ->expects(self::once())
            ->method('getVault')
            ->willReturn($vault);

        $this->sessionContext
            ->expects(self::once())
            ->method('getSidStartTime')
            ->willReturn($sidStartTime);

        self::assertEquals('data', Session::getSessionKey($this->sessionContext));
    }

    /**
     * @throws Exception
     * @throws CryptException
     */
    public function testReKey()
    {
        $sidStartTime = self::$faker->unixTime;

        $key = session_id() . $sidStartTime;

        $vault = $this->createMock(VaultInterface::class);
        $vault->expects(self::once())
              ->method('reKey')
              ->with(self::anything(), $key);

        $this->sessionContext
            ->expects(self::once())
            ->method('getVault')
            ->willReturn($vault);

        $this->sessionContext
            ->expects(self::once())
            ->method('getSidStartTime')
            ->willReturn($sidStartTime);

        $this->sessionContext
            ->expects(self::once())
            ->method('setSidStartTime')
            ->with(self::anything());

        $this->sessionContext
            ->expects(self::once())
            ->method('setVault');

        Session::reKey($this->sessionContext);
    }

    /**
     * @throws CryptException
     */
    public function testSaveSessionKey()
    {
        $this->sessionContext
            ->expects(self::once())
            ->method('getSidStartTime')
            ->willReturn(self::$faker->unixTime);

        $this->sessionContext
            ->expects(self::once())
            ->method('setVault');

        Session::saveSessionKey(self::$faker->name, $this->sessionContext);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionContext = $this->createMock(SessionContextInterface::class);
    }

}
