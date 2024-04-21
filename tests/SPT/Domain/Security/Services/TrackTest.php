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

namespace SPT\Domain\Security\Services;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Domain\Security\Ports\TrackRepository;
use SP\Domain\Security\Services\Track;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class TrackTest
 */
#[Group('unitary')]
class TrackTest extends UnitaryTestCase
{

    private TrackRepository|MockObject  $trackRepository;
    private RequestInterface|MockObject $request;
    private Track                       $track;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackRepository = $this->createMock(TrackRepository::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->track = new Track($this->application, $this->trackRepository, $this->request);
    }
}
