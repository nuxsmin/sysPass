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

namespace SP\Tests\Domain\Auth\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\LoginStatus;
use SP\Domain\Auth\Services\LoginUser;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class LoginUserTest
 */
#[Group('unitary')]
class LoginUserTest extends UnitaryTestCase
{
    private TrackService|MockObject   $trackService;
    private RequestService|MockObject $request;

    private LoginUser                         $loginUser;
    private MockObject|UserPassRecoverService $userPassRecoverService;

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    public function testCheckUser()
    {
        $user = UserDataGenerator::factory()->buildUserData();
        $userDataDto = new UserDataDto($user->mutate(['isDisabled' => false, 'isChangePass' => false]));

        $out = $this->loginUser->checkUser($userDataDto);

        $this->assertEquals(LoginStatus::PASS, $out->getStatus());
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    public function testCheckUserWithDisabled()
    {
        $user = UserDataGenerator::factory()->buildUserData();
        $userDataDto = new UserDataDto($user->mutate(['isDisabled' => true, 'isChangePass' => false]));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('User disabled');

        $this->loginUser->checkUser($userDataDto);
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    public function testCheckUserWithChangePass()
    {
        $user = UserDataGenerator::factory()->buildUserData();
        $userDataDto = new UserDataDto($user->mutate(['isDisabled' => false, 'isChangePass' => true]));

        $this->userPassRecoverService
            ->expects($this->once())
            ->method('add')
            ->with($user->getId(), self::callback(static fn(string $s) => strlen($s) === 32));

        $out = $this->loginUser->checkUser($userDataDto);

        $this->assertEquals(LoginStatus::PASS_RESET_REQUIRED, $out->getStatus());
        $this->assertMatchesRegularExpression('/index\.php\?r=userPassReset%2Freset%2F\w+/', $out->getRedirect());
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    public function testCheckUserWithChangePassWithException()
    {
        $user = UserDataGenerator::factory()->buildUserData();
        $userDataDto = new UserDataDto($user->mutate(['isDisabled' => false, 'isChangePass' => true]));

        $this->userPassRecoverService
            ->expects($this->once())
            ->method('add')
            ->with($user->getId(), self::callback(static fn(string $s) => strlen($s) === 32))
            ->willThrowException(QueryException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Internal error');

        $this->loginUser->checkUser($userDataDto);
    }

    /**
     * @throws Exception
     * @throws ContextException
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trackService = $this->createMock(TrackService::class);
        $this->trackService
            ->expects($this->atLeast(1))
            ->method('buildTrackRequest')
            ->with(LoginUser::class)
            ->willReturn(
                new TrackRequest(
                    self::$faker->unixTime(),
                    self::$faker->colorName(),
                    self::$faker->ipv4(),
                    self::$faker->randomNumber(2)
                )
            );

        $this->request = $this->createMock(RequestService::class);
        $this->userPassRecoverService = $this->createMock(UserPassRecoverService::class);

        $this->loginUser = new LoginUser(
            $this->application,
            $this->trackService,
            $this->request,
            $this->userPassRecoverService
        );
    }
}
