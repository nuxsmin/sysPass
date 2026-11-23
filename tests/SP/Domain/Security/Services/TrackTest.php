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

namespace SP\Tests\Domain\Security\Services;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Domain\Security\Ports\TrackRepository;
use SP\Domain\Security\Services\Track;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Tests\UnitaryTestCase;

/**
 * Class TrackTest
 */
#[Group('unitary')]
class TrackTest extends UnitaryTestCase
{

    private TrackRepository|MockObject $trackRepository;
    private RequestService|MockObject  $request;
    private Track                      $track;

    #[Test]
    public function testSearch()
    {
        $itemSearchData = new ItemSearchDto('test');

        $this->trackRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData, self::anything())
            ->willReturn(new QueryResult());

        $this->track->search($itemSearchData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    #[Test]
    public function testCheckTracking()
    {
        $trackRequest = $this->getTrackRequest();
        $track = new TrackModel([
                                    'ipv4' => $trackRequest->getIpv4(),
                                    'ipv6' => $trackRequest->getIpv6(),
                                    'source' => $trackRequest->getSource(),
                                    'userId' => $trackRequest->getUserId(),
                                    'time' => $trackRequest->getTime()
                                ]);

        $this->trackRepository
            ->expects($this->once())
            ->method('getTracksForClientFromTime')
            ->with($track)
            ->willReturn(new QueryResult([1]));

        $this->assertFalse($this->track->checkTracking($trackRequest));
    }

    /**
     * @return TrackRequest
     * @throws InvalidArgumentException
     */
    private function getTrackRequest(): TrackRequest
    {
        return new TrackRequest(time(), 'test', self::$faker->ipv4(), self::$faker->randomNumber(3));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    #[Test]
    public function testCheckTrackingWithMaxAttempts()
    {
        $trackRequest = $this->getTrackRequest();
        $track = new TrackModel([
                                    'ipv4' => $trackRequest->getIpv4(),
                                    'ipv6' => $trackRequest->getIpv6(),
                                    'source' => $trackRequest->getSource(),
                                    'userId' => $trackRequest->getUserId(),
                                    'time' => $trackRequest->getTime()
                                ]);

        $this->trackRepository
            ->expects($this->once())
            ->method('getTracksForClientFromTime')
            ->with($track)
            ->willReturn(new QueryResult(range(0, 10)));

        $this->assertTrue($this->track->checkTracking($trackRequest));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    #[Test]
    public function testCheckTrackingWithException()
    {
        $trackRequest = $this->getTrackRequest();
        $track = new TrackModel([
                                    'ipv4' => $trackRequest->getIpv4(),
                                    'ipv6' => $trackRequest->getIpv6(),
                                    'source' => $trackRequest->getSource(),
                                    'userId' => $trackRequest->getUserId(),
                                    'time' => $trackRequest->getTime()
                                ]);

        $this->trackRepository
            ->expects($this->once())
            ->method('getTracksForClientFromTime')
            ->with($track)
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        $this->track->checkTracking($trackRequest);
    }

    /**
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws QueryException
     */
    #[Test]
    public function testAdd()
    {
        $trackRequest = $this->getTrackRequest();
        $track = new TrackModel([
                                    'ipv4' => $trackRequest->getIpv4(),
                                    'ipv6' => $trackRequest->getIpv6(),
                                    'source' => $trackRequest->getSource(),
                                    'userId' => $trackRequest->getUserId(),
                                    'time' => $trackRequest->getTime()
                                ]);

        $this->trackRepository
            ->expects($this->once())
            ->method('add')
            ->with($track)
            ->willReturn(new QueryResult(null, 0, 100));

        $this->assertEquals(100, $this->track->add($trackRequest));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    #[Test]
    public function testClear()
    {
        $this->trackRepository
            ->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $this->assertTrue($this->track->clear());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    #[Test]
    public function testClearWithFalse()
    {
        $this->trackRepository
            ->expects($this->once())
            ->method('clear')
            ->willReturn(false);

        $this->assertFalse($this->track->clear());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    #[Test]
    public function testUnlock()
    {
        $this->trackRepository
            ->expects($this->once())
            ->method('unlock')
            ->with(100)
            ->willReturn(1);

        $this->track->unlock(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    #[Test]
    public function testUnlockWithException()
    {
        $this->trackRepository
            ->expects($this->once())
            ->method('unlock')
            ->with(100)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Track not found');

        $this->track->unlock(100);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Test]
    public function testBuildTrackRequest()
    {
        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->willReturn(self::$faker->ipv4());

        $out = $this->track->buildTrackRequest('test');

        $this->assertTrue($out->getTime() < time());
        $this->assertEquals('test', $out->getSource());
        $this->assertNotNull($out->getIpv4());
    }

    #[Test]
    public function testCountForUserAndSourceFromTime(): void
    {
        $this->trackRepository
            ->expects($this->once())
            ->method('countForUserAndSourceFromTime')
            ->with(42, 'auth_token_api', 1700000000)
            ->willReturn(5);

        $this->assertSame(5, $this->track->countForUserAndSourceFromTime(42, 'auth_token_api', 1700000000));
    }

    #[Test]
    public function testAddUserTrackWithIpv4(): void
    {
        $ip = '192.0.2.10';
        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->willReturn($ip);

        $this->trackRepository
            ->expects($this->once())
            ->method('add')
            ->with(new TrackModel([
                'userId' => 7,
                'source' => 'api',
                'ipv4' => inet_pton($ip),
                'ipv6' => null,
            ]))
            ->willReturn(new QueryResult(null, 0, 55));

        $this->assertSame(55, $this->track->addUserTrack(7, 'api'));
    }

    #[Test]
    public function testAddUserTrackWithIpv6(): void
    {
        $ip = '2001:db8::1';
        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->willReturn($ip);

        $this->trackRepository
            ->expects($this->once())
            ->method('add')
            ->with(new TrackModel([
                'userId' => 8,
                'source' => 'api',
                'ipv4' => null,
                'ipv6' => inet_pton($ip),
            ]))
            ->willReturn(new QueryResult(null, 0, 56));

        $this->assertSame(56, $this->track->addUserTrack(8, 'api'));
    }

    #[Test]
    public function testAddUserTrackIgnoresInvalidClientAddress(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->willReturn('not-an-ip');

        $this->trackRepository
            ->expects($this->once())
            ->method('add')
            ->with(new TrackModel([
                'userId' => 9,
                'source' => 'cli',
                'ipv4' => null,
                'ipv6' => null,
            ]))
            ->willReturn(new QueryResult(null, 0, 57));

        $this->assertSame(57, $this->track->addUserTrack(9, 'cli'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackRepository = $this->createMock(TrackRepository::class);
        $this->request = $this->createMock(RequestService::class);

        $this->track = new Track(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->trackRepository,
            $this->request
        );
    }
}
