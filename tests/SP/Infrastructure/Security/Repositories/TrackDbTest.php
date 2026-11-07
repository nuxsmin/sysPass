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
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Infrastructure\Security\Repositories\Track;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the Track repository queries.
 */
#[Group('integration')]
final class TrackDbTest extends DatabaseRepositoryTestCase
{
    private Track $track;

    protected function setUp(): void
    {
        parent::setUp();

        $this->track = new Track($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function searchReturnsTheTracksNewestFirstWithTheirTrackedFlag(): void
    {
        $result = $this->track->search(new ItemSearchDto(), 1529200000);

        self::assertSame(6, $result->getNumRows());
        self::assertSame(6, $result->getTotalNumRows());

        $tracks = $result->getDataAsArray(TrackModel::class);

        self::assertSame([6, 5, 4, 3, 2, 1], array_map(static fn(TrackModel $track) => $track->getId(), $tracks));
        self::assertSame([1, 1, 1, 0, 0, 0], array_map(static fn(TrackModel $track) => $track->getTracked(), $tracks));
    }

    #[Test]
    public function searchFiltersBySourceAndPaginates(): void
    {
        $page = $this->track->search(new ItemSearchDto('login', 1, 2), 1529200000);

        self::assertSame(2, $page->getNumRows());
        self::assertSame(6, $page->getTotalNumRows());
        self::assertSame([5, 4], array_map(
            static fn(TrackModel $track) => $track->getId(),
            $page->getDataAsArray(TrackModel::class)
        ));
    }
}
