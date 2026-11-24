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

namespace SP\Tests\Infrastructure\Crypt;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PDO;
use PHPUnit\Framework\TestCase;
use SP\Infrastructure\Crypt\MasterPasswordActivityLock;
use SP\Infrastructure\Crypt\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Infrastructure\Crypt\MasterPasswordRequestLock;
use SP\Infrastructure\Database\PDOWrapper;

#[Group('integration')]
final class MasterPasswordActivityLockTest extends TestCase
{
    use IsolatedMasterPasswordDatabase;

    #[Test]
    public function requestRehashesLegacyPasswordWithoutWaitingForItsOwnLock(): void
    {
        $reader = $this->isolatedDatabase();
        $reader->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $dsn = 'mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $reader->query('SELECT DATABASE()')->fetchColumn();
        $writer = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $writer->exec('SET innodb_lock_wait_timeout = 1');
        $stmt = $writer->prepare('REPLACE INTO Config (parameter, value) VALUES (?, ?)');
        $stmt->execute(['masterPasswordActivityLock', '1']);
        $oldHash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 4]);
        $stmt->execute(['masterPwd', $oldHash]);
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnOnConsecutiveCalls($reader, $writer);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            $lock->assertCurrentMasterPassword('synthetic');
            $newHash = $writer->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
            self::assertNotSame($oldHash, $newHash);
            self::assertTrue(Hash::checkMasterKey('synthetic', $newHash));
            self::assertFalse(Hash::needsRehash($newHash));
        } finally {
            $lock->release();
        }
    }
    #[Test]
    public function passwordCheckSeesCommittedRehashWithoutBlockingAnotherWriter(): void
    {
        $reader = $this->isolatedDatabase();
        $reader->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $dsn = 'mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $reader->query('SELECT DATABASE()')->fetchColumn();
        $writer = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $writer->exec('SET innodb_lock_wait_timeout = 1');
        $stmt = $writer->prepare('REPLACE INTO Config (parameter, value) VALUES (?, ?)');
        $stmt->execute(['masterPasswordActivityLock', '1']);
        $stmt->execute(['masterPwd', Hash::hashKey('old synthetic')]);
        $lock = new MasterPasswordActivityLock($reader);
        $lock->acquireShared();
        try {
            $reader->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
            $stmt->execute(['masterPwd', Hash::hashKey('new synthetic')]);
            self::assertNotNull($lock->assertCurrentMasterPassword('new synthetic'));
            $stmt->execute(['masterPwd', Hash::hashKey('new synthetic')]);
        } finally {
            $lock->release();
        }
    }
    #[Test]
    public function testIndependentRequestLocksCanBeHeldTogether(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $first = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $second = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $first->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $first->exec("INSERT INTO Config VALUES ('masterPasswordActivityLock', '1') ON DUPLICATE KEY UPDATE value = '1'");
        $second->exec('SET innodb_lock_wait_timeout = 1');
        $wrapper1 = $this->createStub(PDOWrapper::class);
        $wrapper1->method('build')->willReturn($first);
        $wrapper2 = $this->createStub(PDOWrapper::class);
        $wrapper2->method('build')->willReturn($second);
        $config = $this->createStub(ConfigDataInterface::class);
        $request1 = new MasterPasswordRequestLock($config, $wrapper1);
        $request2 = new MasterPasswordRequestLock($config, $wrapper2);
        try {
            $request1->acquire();
            $request2->acquire();
            self::assertTrue($first->inTransaction());
            self::assertTrue($second->inTransaction());
        } finally {
            $request2->release();
            $request1->release();
        }
    }

    #[Test]
    public function testUpgradedHashChangeExcludesOtherRequests(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $first = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $second = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $first->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $first->exec("INSERT INTO Config VALUES ('masterPasswordActivityLock', '1') ON DUPLICATE KEY UPDATE value = '1'");
        $second->exec('SET innodb_lock_wait_timeout = 1');
        $changing = new MasterPasswordActivityLock($first);
        $other = new MasterPasswordActivityLock($second);
        $changing->acquireShared();
        $changing->upgradeToExclusive();
        try {
            $other->acquireShared();
            self::fail('A concurrent request must wait for the hash change');
        } catch (\PDOException) {
            self::assertTrue($first->inTransaction());
        } finally {
            $other->release();
            $changing->release();
        }
        $other->acquireShared();
        self::assertTrue($second->inTransaction());
        $other->release();
    }

    #[Test]
    public function testStaleMasterKeyIsRejectedWhileRequestLockIsHeld(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $connection = new PDO('mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test', 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $connection->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $stmt = $connection->prepare('INSERT INTO Config VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->execute(['masterPasswordActivityLock', '1']);
        $stmt->execute(['masterPwd', Hash::hashKey('current synthetic')]);
        $lock = new MasterPasswordActivityLock($connection);
        $lock->acquireShared();
        $lock->assertCurrentMasterPassword('current synthetic');
        try {
            $lock->assertCurrentMasterPassword('stale synthetic');
            self::fail('A stale master key must be rejected');
        } catch (\SP\Domain\Common\Services\ServiceException) {
            self::assertTrue($connection->inTransaction());
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testRotationGuardWaitsForActiveRequest(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $reader = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $writer = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $reader->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $stmt = $reader->prepare('INSERT INTO Config VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->execute(['masterPasswordActivityLock', '1']);
        $stmt->execute(['masterPwd', Hash::hashKey('old synthetic')]);
        $writer->exec('SET innodb_lock_wait_timeout = 1');
        $activity = new MasterPasswordActivityLock($reader);
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($writer);
        $guard = new MasterPasswordStorageGuard($storage);
        $activity->acquireShared();
        $writer->beginTransaction();
        try {
            $guard->lockAndVerifyCurrentPassword('old synthetic');
            self::fail('Rotation must wait for the request');
        } catch (\SP\Domain\Common\Services\ServiceException) {
            self::assertTrue($reader->inTransaction());
        } finally {
            $writer->rollBack();
            $activity->release();
        }
        $writer->beginTransaction();
        $guard->lockAndVerifyCurrentPassword('old synthetic');
        self::assertTrue($writer->inTransaction());
        $writer->rollBack();
    }

    #[Test]
    public function testSharedRequestWaitsForExclusiveRotation(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $reader = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $writer = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $reader->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $reader->exec("INSERT INTO Config VALUES ('masterPasswordActivityLock', '1') ON DUPLICATE KEY UPDATE value = '1'");
        $reader->exec('SET innodb_lock_wait_timeout = 1');
        $shared = new MasterPasswordActivityLock($reader);
        $exclusive = new MasterPasswordActivityLock($writer);
        $exclusive->acquireExclusive();
        try {
            $shared->acquireShared();
            self::fail('The request must wait for rotation');
        } catch (\PDOException) {
            self::assertTrue($writer->inTransaction());
        } finally {
            $shared->release();
            $exclusive->release();
        }
        $shared->acquireShared();
        self::assertTrue($reader->inTransaction());
        $shared->release();
    }

    #[Test]
    public function testExclusiveRotationWaitsForSharedRequest(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $reader = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $writer = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $reader->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $reader->exec("INSERT INTO Config VALUES ('masterPasswordActivityLock', '1') ON DUPLICATE KEY UPDATE value = '1'");
        $writer->exec('SET innodb_lock_wait_timeout = 1');
        $shared = new MasterPasswordActivityLock($reader);
        $exclusive = new MasterPasswordActivityLock($writer);
        $shared->acquireShared();
        try {
            $exclusive->acquireExclusive();
            self::fail('Rotation must wait for an active request');
        } catch (\PDOException) {
            self::assertTrue($reader->inTransaction());
        } finally {
            $shared->release();
            $exclusive->release();
        }
        $exclusive->acquireExclusive();
        self::assertTrue($writer->inTransaction());
        $exclusive->release();
    }
}
