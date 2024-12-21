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

namespace SP\Tests\Domain\User\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\UserPassRecover as UserPassRecoverModel;
use SP\Domain\User\Ports\UserPassRecoverRepository;
use SP\Domain\User\Services\UserPassRecover;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class UserPassRecoverServiceTest
 */
#[Group('unitary')]
class UserPassRecoverServiceTest extends UnitaryTestCase
{

    private UserPassRecoverRepository|MockObject $userPassRecoverRepository;
    private UserPassRecover                      $userPassRecover;

    /**
     * @throws ServiceException
     */
    public function testGetUserIdForHash()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('getUserIdForHash')
            ->with('a_hash', self::callback(static fn(int $time) => $time < time()))
            ->willReturn(new QueryResult([new UserPassRecoverModel(['userId' => 100])]));

        $out = $this->userPassRecover->getUserIdForHash('a_hash');

        $this->assertEquals(100, $out);
    }

    /**
     * @throws ServiceException
     */
    public function testGetUserIdForHashWithNoRows()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('getUserIdForHash')
            ->with('a_hash', self::callback(static fn(int $time) => $time < time()))
            ->willReturn(new QueryResult());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Wrong hash or expired');

        $this->userPassRecover->getUserIdForHash('a_hash');
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws EnvironmentIsBrokenException
     */
    public function testRequestForUserId()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('getAttemptsByUserId')
            ->with(100, self::callback(static fn(int $time) => $time < time()))
            ->willReturn(1);

        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, self::anything());

        $out = $this->userPassRecover->requestForUserId(100);

        $this->assertNotEmpty($out);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws EnvironmentIsBrokenException
     */
    public function testRequestForUserIdWithMaxAttempts()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('getAttemptsByUserId')
            ->with(100, self::callback(static fn(int $time) => $time < time()))
            ->willReturn(3);

        $this->userPassRecoverRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Attempts exceeded');

        $this->userPassRecover->requestForUserId(100);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testToggleUsedByHash()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('toggleUsedByHash')
            ->with('a_hash', self::callback(static fn(int $time) => $time < time()))
            ->willReturn(1);

        $this->userPassRecover->toggleUsedByHash('a_hash');
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testToggleUsedByHashWithNoRows()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('toggleUsedByHash')
            ->with('a_hash', self::callback(static fn(int $time) => $time < time()))
            ->willReturn(0);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Wrong hash or expired');

        $this->userPassRecover->toggleUsedByHash('a_hash');
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAdd()
    {
        $this->userPassRecoverRepository
            ->expects($this->once())
            ->method('add')
            ->with(100, 'a_hash');

        $this->userPassRecover->add(100, 'a_hash');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPassRecoverRepository = $this->createMock(UserPassRecoverRepository::class);

        $this->userPassRecover = new UserPassRecover($this->application, $this->userPassRecoverRepository);
    }


}
