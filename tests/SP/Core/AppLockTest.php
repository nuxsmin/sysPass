<?php
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

declare(strict_types=1);

namespace SP\Tests\Core;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Core\AppLock;
use SP\Infrastructure\File\FileHandlerFactory;

/**
 * Class AppLockTest
 */
#[Group('unitary')]
final class AppLockTest extends TestCase
{
    private string $lockFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockFile = sys_get_temp_dir() . '/syspass-applock-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        @unlink($this->lockFile);

        parent::tearDown();
    }

    #[Test]
    public function tryLockIsExclusiveAndOnlyTheOwnerReleases(): void
    {
        $first = $this->buildLock();
        $second = $this->buildLock();

        self::assertTrue($first->tryLock(0, 'upgrade'));
        self::assertFalse($second->tryLock(0, 'upgrade'));

        $second->unlock();

        self::assertFileExists($this->lockFile);

        $first->unlock();

        self::assertFileDoesNotExist($this->lockFile);
    }

    #[Test]
    public function recoversExpiredLock(): void
    {
        file_put_contents(
            $this->lockFile,
            json_encode(
                [
                    'time' => time() - 7200,
                    'userId' => 0,
                    'subject' => 'upgrade',
                    'token' => 'expired',
                    'expires' => time() - 3600
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $lock = $this->buildLock();

        self::assertTrue($lock->tryLock(0, 'upgrade'));
    }

    #[Test]
    public function recoversAbandonedOwnedLock(): void
    {
        file_put_contents(
            $this->lockFile,
            json_encode(
                [
                    'time' => time(),
                    'userId' => 0,
                    'subject' => 'upgrade',
                    'token' => 'abandoned',
                    'expires' => time() + 3600
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $lock = $this->buildLock();

        self::assertTrue($lock->tryLock(0, 'upgrade'));
    }

    #[Test]
    public function recoversLegacyStaleLockWithoutExpiry(): void
    {
        file_put_contents(
            $this->lockFile,
            json_encode(
                [
                    'time' => time() - 7200,
                    'userId' => 0,
                    'subject' => 'upgrade-durable-payloads'
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $lock = $this->buildLock();

        self::assertTrue($lock->tryLock(0, 'upgrade'));
    }

    #[Test]
    public function doesNotStealLiveMaintenanceLock(): void
    {
        file_put_contents(
            $this->lockFile,
            json_encode(
                [
                    'time' => time(),
                    'userId' => 7,
                    'subject' => 'config'
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $lock = $this->buildLock();

        self::assertFalse($lock->tryLock(0, 'upgrade'));
    }

    #[Test]
    public function ignoresExpiredLockMetadata(): void
    {
        file_put_contents(
            $this->lockFile,
            json_encode(
                [
                    'time' => time() - 7200,
                    'userId' => 7,
                    'subject' => 'config',
                    'expires' => time() - 1
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertFalse($this->buildLock()->getLock());
    }

    #[Test]
    public function persistsMaintenanceLockUntilUnlocked(): void
    {
        $lock = $this->buildLock();

        $lock->lock(7, 'config');

        self::assertSame(7, $lock->getLock());

        $lock->unlock();

        self::assertFileDoesNotExist($this->lockFile);
    }

    private function buildLock(): AppLock
    {
        return new AppLock($this->lockFile, new FileHandlerFactory());
    }
}
