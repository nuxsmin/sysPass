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

namespace SP\Tests\Infrastructure\Security\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Security\Models\Eventlog as EventlogModel;
use SP\Infrastructure\Security\Repositories\Eventlog;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the event log repository queries.
 */
#[Group('integration')]
final class EventlogDbTest extends DatabaseRepositoryTestCase
{
    private Eventlog $eventlog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventlog = new Eventlog($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function searchReturnsTheEventLogsWithTheirUnixTimestamp(): void
    {
        $result = $this->eventlog->search(new ItemSearchDto());

        self::assertSame(14, $result->getNumRows());
        self::assertSame(14, $result->getTotalNumRows());

        $eventlogs = $result->getDataAsArray(EventlogModel::class);

        self::assertSame(14, $eventlogs[0]->getId());
        self::assertSame(1527807531, $eventlogs[0]->getDate());
    }
}
