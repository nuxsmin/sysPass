<?php

declare(strict_types=1);
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

namespace SP\Tests\Domain\Security\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\Security\UseCases\ClearTracksUseCase;
use SP\Domain\Security\UseCases\Dtos\ClearTracksCommand;
use SP\Domain\Security\UseCases\Dtos\SearchTracksCommand;
use SP\Domain\Security\UseCases\Dtos\UnlockTrackCommand;
use SP\Domain\Security\UseCases\SearchTracksUseCase;
use SP\Domain\Security\UseCases\UnlockTrackUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the track use cases.
 */
#[Group('unitary')]
final class TrackUseCasesTest extends TestCase
{
    private TrackService&MockObject $service;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(TrackService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function clearEmitsTheEvent(): void
    {
        $this->service->method('clear')->willReturn(true);

        $result = (new ClearTracksUseCase($this->service, $this->eventEmitter))
            ->execute(new ClearTracksCommand());

        self::assertTrue($result->cleared);
        self::assertSame('clear.track', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function unlockEmitsTheEvent(): void
    {
        $result = (new UnlockTrackUseCase($this->service, $this->eventEmitter))
            ->execute(new UnlockTrackCommand(7));

        self::assertSame(7, $result->id);
        self::assertSame('unlock.track', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function searchReturnsTheTracks(): void
    {
        $this->service->method('search')->willReturn(QueryResult::withTotalNumRows([], 2));

        $result = (new SearchTracksUseCase($this->service))
            ->execute(new SearchTracksCommand(new ItemSearchDto()));

        self::assertSame([], $result->tracks);
        self::assertSame(2, $result->total);
    }
}
