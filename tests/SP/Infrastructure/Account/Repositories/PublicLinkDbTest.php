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
    public function staleReadsCanBothIncrementTheSameLink(): void
    {
        $link = $this->publicLink->getAll()->getDataAsArray(PublicLinkModel::class)[0];
        $first = $this->publicLink->getByHash($link->getHash())->getData(PublicLinkModel::class);
        $second = $this->publicLink->getByHash($link->getHash())->getData(PublicLinkModel::class);
        $initial = $first->getCountViews();

        self::assertTrue($this->publicLink->addLinkView($first));
        self::assertTrue($this->publicLink->addLinkView($second));

        $query = getDbHandler()->getConnection()->prepare('SELECT countViews FROM PublicLink WHERE hash = ?');
        $query->execute([$link->getHash()]);
        self::assertSame($initial + 2, (int) $query->fetchColumn());
    }

}
