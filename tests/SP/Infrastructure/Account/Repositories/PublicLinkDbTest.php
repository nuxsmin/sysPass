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

namespace SP\Tests\Infrastructure\Account\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Infrastructure\Account\Repositories\PublicLink;
use SP\Tests\DatabaseRepositoryTestCase;
use function SP\Tests\getDbHandler;

#[Group('integration')]
final class PublicLinkDbTest extends DatabaseRepositoryTestCase
{
    private PublicLink $publicLink;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicLink = new PublicLink($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function getAllReturnsTheLinksWithTheirUserAndAccountNames(): void
    {
        $links = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class);

        self::assertCount(2, $links);
        self::assertSame('sysPass Admin', $links[0]->getUserName());
        self::assertSame('admin', $links[0]->getUserLogin());
        self::assertSame('Google', $links[0]->getAccountName());
        self::assertSame('Apple', $links[1]->getAccountName());
    }

    #[Test]
    public function staleReadsRespectMaxCountViews(): void
    {
        $pdo = getDbHandler()->getConnection();
        $link = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class)[0];

        $update = $pdo->prepare('UPDATE PublicLink SET dateExpire = ?, countViews = maxCountViews - 1 WHERE hash = ?');
        $update->execute([time() + 600, $link->getHash()]);

        $first = $this->publicLink->getByHash($link->getHash())->getData(PublicLinkModel::class);
        $second = $this->publicLink->getByHash($link->getHash())->getData(PublicLinkModel::class);

        self::assertTrue($this->publicLink->addLinkView($first));
        self::assertFalse($this->publicLink->addLinkView($second));

        $query = $pdo->prepare('SELECT countViews, maxCountViews FROM PublicLink WHERE hash = ?');
        $query->execute([$link->getHash()]);
        $row = $query->fetch(\PDO::FETCH_ASSOC);
        self::assertSame((int) $row['maxCountViews'], (int) $row['countViews']);
    }

    #[Test]
    public function expiredLinkCannotIncrementView(): void
    {
        $pdo = getDbHandler()->getConnection();
        $link = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class)[0];

        $update = $pdo->prepare('UPDATE PublicLink SET dateExpire = ?, countViews = 0 WHERE hash = ?');
        $update->execute([time() - 60, $link->getHash()]);

        $expired = $this->publicLink->getByHash($link->getHash())->getData(PublicLinkModel::class);

        self::assertFalse($this->publicLink->addLinkView($expired));

        $query = $pdo->prepare('SELECT countViews FROM PublicLink WHERE hash = ?');
        $query->execute([$link->getHash()]);
        self::assertSame(0, (int) $query->fetchColumn());
    }

    #[Test]
    public function deleteByItemIdsRemovesAllMatchingLinksAndHandlesEmptyArray(): void
    {
        $pdo = getDbHandler()->getConnection();
        $links = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class);
        self::assertCount(2, $links);

        $itemIds = array_map(static fn(PublicLinkModel $l) => (int)$l->getItemId(), $links);

        $this->publicLink->deleteByItemIds([]);
        self::assertSame(2, (int)$pdo->query('SELECT COUNT(*) FROM PublicLink')->fetchColumn());

        $this->publicLink->deleteByItemIds([$itemIds[0], $itemIds[1], $itemIds[0]]);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM PublicLink')->fetchColumn());
    }

    #[Test]
    public function deleteByItemIdRemovesOnlyMatchingLinks(): void
    {
        $pdo = getDbHandler()->getConnection();
        $links = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class);
        self::assertCount(2, $links);

        $firstItemId = (int)$links[0]->getItemId();
        $this->publicLink->deleteByItemId($firstItemId);

        $remaining = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class);
        self::assertCount(1, $remaining);
        self::assertNotSame($firstItemId, (int)$remaining[0]->getItemId());
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM PublicLink')->fetchColumn());
    }
}
