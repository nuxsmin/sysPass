<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Infrastructure\Auth\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Infrastructure\Auth\Repositories\AuthToken;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the AuthToken repository queries.
 */
#[Group('integration')]
final class AuthTokenDbTest extends DatabaseRepositoryTestCase
{
    private AuthToken $authToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authToken = new AuthToken($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function searchFiltersByTheUserLoginAndName(): void
    {
        $result = $this->authToken->search(new ItemSearchDto('demo'));

        self::assertSame(1, $result->getNumRows());
        self::assertSame(2, $result->getDataAsArray()[0]->userId);
        self::assertNull($result->getDataAsArray()[0]->getToken());
    }

    #[Test]
    public function searchPaginatesTheTokens(): void
    {
        $page = $this->authToken->search(new ItemSearchDto('sysPass', 1, 1));

        self::assertSame(1, $page->getNumRows());
        self::assertSame(5, $page->getTotalNumRows());
    }

    #[Test]
    public function searchForNonAdminIsolatesUserRowsEvenWhenOrFilterMatchesAnotherUser(): void
    {
        $this->context->setUserData($this->context->getUserData()->mutate(['id' => 2, 'isAdminApp' => false]));

        $ownResult = $this->authToken->search(new ItemSearchDto('demo'));
        self::assertSame(1, $ownResult->getNumRows());
        self::assertSame(2, $ownResult->getDataAsArray()[0]->userId);

        $otherUserMatch = $this->authToken->search(new ItemSearchDto('admin'));
        self::assertSame(0, $otherUserMatch->getNumRows());
    }

    #[Test]
    public function createAllowsMultipleActionsForSameUserAndRejectsSameUserAndAction(): void
    {
        $first = new \SP\Domain\Auth\Models\AuthToken([
            'userId' => 2,
            'token' => bin2hex(random_bytes(32)),
            'actionId' => \SP\Domain\Core\Acl\AclActionsInterface::CLIENT_SEARCH,
            'createdBy' => 1,
        ]);
        $second = new \SP\Domain\Auth\Models\AuthToken([
            'userId' => 2,
            'token' => bin2hex(random_bytes(32)),
            'actionId' => \SP\Domain\Core\Acl\AclActionsInterface::CATEGORY_SEARCH,
            'createdBy' => 1,
        ]);

        $firstId = $this->authToken->create($first)->getLastId();
        $secondId = $this->authToken->create($second)->getLastId();

        self::assertGreaterThan(0, $firstId);
        self::assertGreaterThan($firstId, $secondId);

        $this->expectException(\SP\Domain\Core\Exceptions\DuplicatedItemException::class);
        $this->expectExceptionMessage('Authorization already exist');

        $this->authToken->create(new \SP\Domain\Auth\Models\AuthToken([
            'userId' => 2,
            'token' => bin2hex(random_bytes(32)),
            'actionId' => \SP\Domain\Core\Acl\AclActionsInterface::CATEGORY_SEARCH,
            'createdBy' => 1,
        ]));
    }
}
