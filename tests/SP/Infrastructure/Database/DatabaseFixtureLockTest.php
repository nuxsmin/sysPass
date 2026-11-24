<?php
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

declare(strict_types=1);

namespace SP\Tests\Infrastructure\Database;

use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Tests\DatabaseTrait;
use function SP\Tests\getDbHandler;

#[Group('integration')]
final class DatabaseFixtureLockTest extends TestCase
{
    use DatabaseTrait;

    #[Test]
    public function fixtureReloadReleasesLocksHeldByUnreachableRequestCycles(): void
    {
        self::loadFixtures();
        $connection = getDbHandler()->getConnection();
        $connection->beginTransaction();
        $connection->query("SELECT value FROM Config WHERE parameter = 'masterPwd' LOCK IN SHARE MODE")->fetchColumn();

        $request = new class ($connection) {
            public mixed $cycle;

            public function __construct(public PDO $connection)
            {
                $this->cycle = $this;
            }
        };
        $reference = \WeakReference::create($request);
        unset($request, $connection);

        self::loadFixtures();

        self::assertNull($reference->get());
        self::assertGreaterThan(0, self::getRowCount('Config'));
    }
}
