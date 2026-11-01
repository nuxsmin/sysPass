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
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Security\Ports\EventlogService;
use SP\Domain\Security\UseCases\ClearEventlogUseCase;
use SP\Domain\Security\UseCases\Dtos\ClearEventlogCommand;
use SP\Domain\Security\UseCases\Dtos\SearchEventlogCommand;
use SP\Domain\Security\UseCases\SearchEventlogUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the event log use cases.
 */
#[Group('unitary')]
final class EventlogUseCasesTest extends TestCase
{
    private EventlogService&MockObject $service;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(EventlogService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function clearEmptiesTheLogAndEmitsTheEvent(): void
    {
        $this->service->method('clear')->willReturn(true);

        $result = (new ClearEventlogUseCase($this->service, $this->eventEmitter))
            ->execute(new ClearEventlogCommand());

        self::assertTrue($result->cleared);

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('clear.eventlog', $event['name']);
        self::assertSame(
            'Event log cleared',
            (new EventMessageFormatter())->composeText($event['message'])
        );
    }

    #[Test]
    public function searchReturnsTheEventLogs(): void
    {
        $queryResult = QueryResult::withTotalNumRows([], 4);
        $this->service->method('search')->willReturn($queryResult);

        $result = (new SearchEventlogUseCase($this->service))
            ->execute(new SearchEventlogCommand(new ItemSearchDto()));

        self::assertSame([], $result->eventlogs);
        self::assertSame(4, $result->total);
    }
}
