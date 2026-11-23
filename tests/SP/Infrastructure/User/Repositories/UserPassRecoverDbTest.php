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

namespace SP\Tests\Infrastructure\User\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\User\Models\UserPassRecover as UserPassRecoverModel;
use SP\Infrastructure\User\Repositories\UserPassRecover;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the UserPassRecover repository queries.
 */
#[Group('integration')]
final class UserPassRecoverDbTest extends DatabaseRepositoryTestCase
{
    private const EXISTING_HASH = '086ab10608f6884ef8019a452735ac0f';

    private UserPassRecover $userPassRecover;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPassRecover = new UserPassRecover(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getAttemptsByUserIdCountsTheUnusedAttemptsInTheTimeWindow(): void
    {
        self::assertSame(2, $this->userPassRecover->getAttemptsByUserId(2, 1529275200));
        self::assertSame(1, $this->userPassRecover->getAttemptsByUserId(2, 1529275300));
        self::assertSame(0, $this->userPassRecover->getAttemptsByUserId(2, 1529275400));
    }

    #[Test]
    public function addCreatesAnUnusedAttemptWithTheCurrentDate(): void
    {
        $this->userPassRecover->add(3, 'newhash');

        self::assertSame(1, $this->userPassRecover->getAttemptsByUserId(3, time() - 3600));

        $attempt = $this->userPassRecover->getUserIdForHash('newhash', time() - 3600)
                                         ->getData(UserPassRecoverModel::class);

        self::assertSame(3, $attempt->getUserId());
        self::assertSame('newhash', $attempt->getHash());
        self::assertFalse($attempt->isUsed());
    }

    #[Test]
    public function toggleUsedByHashMarksTheAttemptAsUsed(): void
    {
        self::assertSame(1, $this->userPassRecover->toggleUsedByHash(self::EXISTING_HASH, 0));
        self::assertSame(0, $this->userPassRecover->getUserIdForHash(self::EXISTING_HASH, 0)->getNumRows());

        self::assertSame(0, $this->userPassRecover->toggleUsedByHash(self::EXISTING_HASH, 0));
    }

    #[Test]
    public function toggleUsedByHashIgnoresExpiredAttempts(): void
    {
        self::assertSame(0, $this->userPassRecover->toggleUsedByHash(self::EXISTING_HASH, 1529275400));
        self::assertSame(1, $this->userPassRecover->getUserIdForHash(self::EXISTING_HASH, 0)->getNumRows());
    }

    #[Test]
    public function getUserIdForHashReturnsTheUnusedAttemptInTheTimeWindow(): void
    {
        $attempt = $this->userPassRecover->getUserIdForHash(self::EXISTING_HASH, 1529275200)
                                         ->getData(UserPassRecoverModel::class);

        self::assertSame(2, $attempt->getUserId());
        self::assertSame(self::EXISTING_HASH, $attempt->getHash());

        self::assertSame(0, $this->userPassRecover->getUserIdForHash(self::EXISTING_HASH, 1529275400)->getNumRows());
        self::assertSame(0, $this->userPassRecover->getUserIdForHash('missing', 0)->getNumRows());
    }
}
