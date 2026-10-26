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
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Auth\Services\LoginBase;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Tests\UnitaryTestCase;

/**
 * Class LoginBaseTest
 */
#[Group('unitary')]
class LoginBaseTest extends UnitaryTestCase
{
    private LoginBase                 $loginBase;
    private RequestService|MockObject $request;
    private TrackService|MockObject   $trackService;

    public function testCheckTracking()
    {
        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(true);

        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Attempts exceeded');

        $this->loginBase->check();
    }

    public function testCheckTrackingWithNoTracking()
    {
        $this->trackService
            ->expects($this->once())
            ->method('checkTracking')
            ->willReturn(false);

        $this->loginBase->check();
    }

    public function testAddTracking()
    {
        $this->trackService
            ->expects($this->once())
            ->method('add');

        $this->loginBase->add();
    }

    public function testAddTrackingWithException()
    {
        $this->trackService
            ->expects($this->once())
            ->method('add')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Internal error');

        $this->loginBase->add();
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
            ->willReturn(
                new TrackRequest(
                    self::$faker->unixTime(),
                    self::$faker->colorName(),
                    self::$faker->ipv4(),
                    self::$faker->randomNumber(2)
                )
            );

        $this->request = $this->createMock(RequestService::class);

        $this->loginBase = new class($this->application, $this->trackService, $this->request) extends LoginBase {
            public function check(): void
            {
                $this->checkTracking();
            }

            public function add(): void
            {
                $this->addTracking();
            }
        };
    }
}
