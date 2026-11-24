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
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Infrastructure\Crypt\MasterPasswordRequestLock;
use SP\Infrastructure\Database\PDOWrapper;
use SP\Tests\UnitaryTestCase;

#[Group('integration')]
final class MasterPasswordLockInitializationTest extends UnitaryTestCase
{
    use IsolatedMasterPasswordDatabase;

    #[Test]
    public function innodbDeadlockRollsBackNestedTransactionAndRejectsFurtherWrites(): void
    {
        $admin = $this->isolatedDatabase();
        $admin->exec('CREATE TABLE deadlock_test (id INT PRIMARY KEY, value INT NOT NULL) ENGINE=InnoDB');
        $admin->exec('INSERT INTO deadlock_test VALUES (1, 0), (2, 0)');
        $name = $admin->query('SELECT DATABASE()')->fetchColumn();
        $pdo = new PDO('mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $name, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $database = new \SP\Infrastructure\Database\Database($storage, $this->eventDispatcherStub);
        $pipes = [];
        $worker = proc_open([PHP_BINARY, __DIR__ . '/DeadlockWorker.inc'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($worker);
        try {
            stream_set_timeout($pipes[1], 10);
            $database->beginTransaction();
            $pdo->exec('UPDATE deadlock_test SET value = value + 1 WHERE id = 1');
            $database->beginTransaction();
            fwrite($pipes[0], $name . "\n");
            self::assertSame("ready\n", fgets($pipes[1]));
            fwrite($pipes[0], "continue\n");
            $deadline = microtime(true) + 5;
            do {
                $waiting = (int)$admin->query('SELECT COUNT(*) FROM information_schema.INNODB_LOCK_WAITS')->fetchColumn();
                if ($waiting === 0) {
                    usleep(20000);
                }
            } while ($waiting === 0 && microtime(true) < $deadline);
            self::assertGreaterThan(0, $waiting, 'The competing transaction must wait before completing the deadlock');
            try {
                $pdo->exec('UPDATE deadlock_test SET value = value + 1 WHERE id = 2');
                self::fail('InnoDB must choose the smaller transaction as the deadlock victim');
            } catch (\PDOException $e) {
                self::assertSame(1213, $e->errorInfo[1]);
            }
            self::assertFalse($database->rollbackTransaction());
            try {
                $database->runQueryRaw('UPDATE deadlock_test SET value = 9 WHERE id = 1');
                self::fail('A deadlocked transaction must reject subsequent writes');
            } catch (\SP\Domain\Core\Exceptions\QueryException) {
                self::assertTrue(true);
            }
            self::assertSame("done\n", fgets($pipes[1]));
            self::assertSame([1, 1], array_map('intval', $admin->query('SELECT value FROM deadlock_test WHERE id IN (1, 2) ORDER BY id')->fetchAll(PDO::FETCH_COLUMN)));
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_terminate($worker);
            proc_close($worker);
        }
    }

    #[\PHPUnit\Framework\Attributes\TestWith([false])]
    #[\PHPUnit\Framework\Attributes\TestWith([true])]
    #[Test]
    public function lostNestedTransactionDoesNotCommitEarlierWrites(bool $killConnection): void
    {
        $admin = $this->isolatedDatabase();
        $admin->exec('CREATE TABLE changes_test (id INT PRIMARY KEY) ENGINE=InnoDB');
        $dbName = $admin->query('SELECT DATABASE()')->fetchColumn();
        $pdo = new PDO('mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $dbName, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $database = new \SP\Infrastructure\Database\Database($storage, $this->eventDispatcherStub);
        self::assertTrue($database->beginTransaction());
        $pdo->exec('INSERT INTO changes_test VALUES (1)');
        self::assertTrue($database->beginTransaction());
        if ($killConnection) {
            $connectionId = (int)$pdo->query('SELECT CONNECTION_ID()')->fetchColumn();
            $admin->exec('KILL CONNECTION ' . $connectionId);
        } else {
            // Simulate a server-side rollback without unwinding PHP's scope stack.
            $pdo->exec('ROLLBACK');
        }
        self::assertFalse($database->rollbackTransaction());
        try {
            $database->runQueryRaw('INSERT INTO changes_test VALUES (2)');
            self::fail('Writes must be rejected after the transaction is lost');
        } catch (\SP\Domain\Core\Exceptions\QueryException) {
            self::assertTrue(true);
        }
        try {
            self::assertFalse($database->endTransaction());
        } catch (\PDOException) {
            self::assertTrue($killConnection);
        }
        self::assertSame(0, (int)$admin->query('SELECT COUNT(*) FROM changes_test')->fetchColumn());
    }

    #[Test]
    public function engineCannotChangeBetweenVerificationAndTransactionEnd(): void
    {
        $pdo = $this->isolatedDatabase();
        foreach (['Account', 'AccountHistory', 'CustomFieldData', 'Config', 'PluginData', 'AuthToken'] as $table) {
            $pdo->exec("CREATE TABLE `$table` (id INT PRIMARY KEY) ENGINE=InnoDB");
        }
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $other = new PDO('mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $dbName, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $other->exec('SET lock_wait_timeout = 1');
        $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $pdo->beginTransaction();
        try {
            (new \SP\Infrastructure\Crypt\MasterPasswordStorageGuard($storage))->assertTransactional();
            try {
                $other->exec('ALTER TABLE Account ENGINE=MyISAM');
                self::fail('Changing a verified table must wait for the rotation transaction');
            } catch (\PDOException $e) {
                self::assertSame(1205, $e->errorInfo[1]);
            }
        } finally {
            $pdo->rollBack();
        }
        $other->exec('ALTER TABLE Account ENGINE=MyISAM');
        self::assertSame('MyISAM', $other->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Account'")->fetchColumn());
    }

    #[\PHPUnit\Framework\Attributes\TestWith([0])]
    #[\PHPUnit\Framework\Attributes\TestWith([1])]
    #[\PHPUnit\Framework\Attributes\TestWith([2])]
    #[Test]
    public function testRotatedPasswordRejectsAmbiguousOldSessionKey(int $scenario): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $newPassword = $scenario === 0 ? str_repeat('é', 36) . 'B' : str_repeat('x', 73);
        $oldPassword = $scenario === 0 ? str_repeat('é', 36) . 'A' : hash('sha256', $newPassword);
        $hash = $scenario === 2
            ? \SP\Domain\Crypt\Services\Hash::hashKey($newPassword)
            : \SP\Domain\Crypt\Services\Hash::hashMasterKey($newPassword);
        $insert->execute(['masterPwd', $hash]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturn($pdo);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            $this->expectException(\SP\Domain\Common\Services\ServiceException::class);
            $lock->assertCurrentMasterPassword($oldPassword);
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testRehashRejectsCompetingMultibytePasswordWithSameBcryptPrefix(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $oldPassword = str_repeat('é', 36) . 'A';
        $newPassword = str_repeat('é', 36) . 'B';
        $oldHash = '$sp-sha256$' . password_hash(hash('sha256', $oldPassword), PASSWORD_BCRYPT, ['cost' => 4]);
        $newHash = \SP\Domain\Crypt\Services\Hash::hashMasterKey($newPassword);
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $writer = new PDO('mysql:host=sp-masterpass-verify-db;dbname=' . $dbName, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $calls = 0;
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnCallback(static function () use ($pdo, $writer, $newHash, &$calls): PDO {
            if ($calls++ === 0) {
                return $pdo;
            }
            $update = $writer->prepare("UPDATE Config SET value = ? WHERE parameter = 'masterPwd'");
            $update->execute([$newHash]);
            return $writer;
        });
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            try {
                $lock->assertCurrentMasterPassword($oldPassword);
                self::fail('A competing multibyte password must invalidate the old session');
            } catch (\SP\Domain\Common\Services\ServiceException $e) {
                self::assertSame(\SP\Domain\Crypt\Ports\MasterPasswordRequestLock::STALE_MASTER_PASSWORD, $e->getCode());
            }
            self::assertSame($newHash, $writer->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn());
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testWriteFenceRejectsLockLostAfterLivenessCheck(): void
    {
        $setup = $this->isolatedDatabase();
        $setup->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $setup->exec('CREATE TABLE vault_write (id INT PRIMARY KEY, key_version VARCHAR(10)) ENGINE=InnoDB');
        $insert = $setup->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', \SP\Domain\Crypt\Services\Hash::hashKey('old synthetic')]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $dbName = $setup->query('SELECT DATABASE()')->fetchColumn();
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=' . $dbName;
        $main = new class($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]) extends PDO {
            public ?\Closure $beforeFence = null;
            public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
            {
                if ($this->beforeFence !== null && str_contains($query, "parameter = 'masterPasswordActivityLock' LOCK IN SHARE MODE")) {
                    $callback = $this->beforeFence;
                    $this->beforeFence = null;
                    $callback();
                }
                return $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
            }
        };
        $gate = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rotate = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $gateId = (int)$gate->query('SELECT CONNECTION_ID()')->fetchColumn();
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturn($gate);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        $lock->assertCurrentMasterPassword('old synthetic');
        $main->beforeFence = static function () use ($rotate, $gateId): void {
            $rotate->exec('KILL CONNECTION ' . $gateId);
            $rotate->beginTransaction();
            $rotate->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' FOR UPDATE")->fetchColumn();
            $update = $rotate->prepare("UPDATE Config SET value = ? WHERE parameter = 'masterPwd'");
            $update->execute([\SP\Domain\Crypt\Services\Hash::hashKey('new synthetic')]);
            $rotate->commit();
        };
        $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($main);
        $events = $this->createStub(\SP\Domain\Core\Events\EventDispatcherInterface::class);
        $db = new \SP\Infrastructure\Database\Database($storage, $events, $lock);
        $query = (new \Aura\SqlQuery\QueryFactory('mysql'))->newInsert()->into('vault_write')->cols(['id' => 1, 'key_version' => 'old']);
        try {
            try {
                $db->runQuery(\SP\Infrastructure\Database\QueryData::build($query));
                self::fail('A write must recheck its key after a lost-lock race');
            } catch (\SP\Domain\Core\Exceptions\SPException|\PDOException) {
                self::assertSame(0, (int)$rotate->query('SELECT COUNT(*) FROM vault_write')->fetchColumn());
            }
        } finally {
            try { $lock->release(); } catch (\Throwable) {}
            try { unset($db, $lock); } catch (\Throwable) {}
        }
    }

    #[Test]
    public function testWriteTransactionKeepsRotationBlockedAfterRequestLockIsLost(): void
    {
        $main = $this->isolatedDatabase();
        $main->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $main->exec('CREATE TABLE vault_write (id INT PRIMARY KEY, key_version VARCHAR(10)) ENGINE=InnoDB');
        $insert = $main->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', \SP\Domain\Crypt\Services\Hash::hashKey('old synthetic')]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $dbName = $main->query('SELECT DATABASE()')->fetchColumn();
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=' . $dbName;
        $gate = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rotate = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $gateId = (int)$gate->query('SELECT CONNECTION_ID()')->fetchColumn();
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturn($gate);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        $lock->assertCurrentMasterPassword('old synthetic');
        $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($main);
        $events = $this->createStub(\SP\Domain\Core\Events\EventDispatcherInterface::class);
        $db = new \SP\Infrastructure\Database\Database($storage, $events, $lock);
        $query = (new \Aura\SqlQuery\QueryFactory('mysql'))->newInsert()->into('vault_write')->cols(['id' => 1, 'key_version' => 'old']);
        try {
            $db->beginTransaction();
            $db->runQuery(\SP\Infrastructure\Database\QueryData::build($query));
            $rotate->exec('KILL CONNECTION ' . $gateId);
            $rotate->exec('SET innodb_lock_wait_timeout = 1');
            $rotate->beginTransaction();
            try {
                $rotate->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' FOR UPDATE");
                self::fail('Rotation must wait for the in-flight write transaction');
            } catch (\PDOException $e) {
                self::assertSame(1205, $e->errorInfo[1] ?? null);
            }
            $db->endTransaction();
            self::assertSame('1', $rotate->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' FOR UPDATE")->fetchColumn());
            $rotate->rollBack();
            self::assertSame(1, (int)$rotate->query('SELECT COUNT(*) FROM vault_write')->fetchColumn());
        } finally {
            if ($main->inTransaction()) { $main->rollBack(); }
            if ($rotate->inTransaction()) { $rotate->rollBack(); }
            try { $lock->release(); } catch (\Throwable) {}
            try { unset($db, $lock); } catch (\Throwable) {}
        }
    }

    #[Test]
    public function testLostRequestLockCannotCommitAnOldKeyWriteAfterRotation(): void
    {
        $main = $this->isolatedDatabase();
        $main->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $main->exec('CREATE TABLE vault_write (id INT PRIMARY KEY, key_version VARCHAR(10)) ENGINE=InnoDB');
        $insert = $main->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', \SP\Domain\Crypt\Services\Hash::hashKey('old synthetic')]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $dbName = $main->query('SELECT DATABASE()')->fetchColumn();
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=' . $dbName;
        $gate = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rotate = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $gateId = (int)$gate->query('SELECT CONNECTION_ID()')->fetchColumn();
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturn($gate);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        $lock->assertCurrentMasterPassword('old synthetic');
        $rotate->exec('KILL CONNECTION ' . $gateId);
        $rotate->beginTransaction();
        $rotate->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' FOR UPDATE")->fetchColumn();
        $update = $rotate->prepare("UPDATE Config SET value = ? WHERE parameter = 'masterPwd'");
        $update->execute([\SP\Domain\Crypt\Services\Hash::hashKey('new synthetic')]);
        $rotate->commit();

        try {
            $lock->assertCurrentMasterPassword('old synthetic');
            self::fail('A cached password must not be accepted after the lock is lost');
        } catch (\SP\Domain\Core\Exceptions\SPException|\PDOException) {
            self::assertTrue(true);
        }

        $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($main);
        $events = $this->createStub(\SP\Domain\Core\Events\EventDispatcherInterface::class);
        $db = new \SP\Infrastructure\Database\Database($storage, $events, $lock);
        $query = (new \Aura\SqlQuery\QueryFactory('mysql'))->newInsert()->into('vault_write')->cols(['id' => 1, 'key_version' => 'old']);
        try {
            try {
                $db->runQuery(\SP\Infrastructure\Database\QueryData::build($query));
                self::fail('A write under a lost lock must fail');
            } catch (\SP\Domain\Core\Exceptions\SPException|\PDOException) {
                self::assertSame(0, (int)$rotate->query('SELECT COUNT(*) FROM vault_write')->fetchColumn());
            }
        } finally {
            try { $lock->release(); } catch (\Throwable) {}
            try { unset($db, $lock); } catch (\Throwable) {}
        }
    }

    #[Test]
    public function testRehashAllowsOperatorManagedConfigTriggers(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $pdo->exec('CREATE TABLE side_effect (id INT) ENGINE=MyISAM');
        $oldHash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 4]);
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $pdo->exec('CREATE TRIGGER config_update BEFORE UPDATE ON Config FOR EACH ROW INSERT INTO side_effect VALUES (1)');
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $writer = new PDO('mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $dbName, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnOnConsecutiveCalls($pdo, $writer);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            $lock->assertCurrentMasterPassword('synthetic');
            $newHash = $writer->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
            self::assertNotSame($oldHash, $newHash);
            self::assertTrue(\SP\Domain\Crypt\Services\Hash::checkMasterKey('synthetic', $newHash));
            self::assertSame(1, (int)$writer->query('SELECT COUNT(*) FROM side_effect')->fetchColumn());
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testRehashFailureDoesNotRejectAValidPassword(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $oldHash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 4]);
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $calls = 0;
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnCallback(static function () use ($pdo, &$calls): PDO {
            if ($calls++ === 0) {
                return $pdo;
            }
            throw new \RuntimeException('Synthetic connection failure');
        });
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            $lock->assertCurrentMasterPassword('synthetic');
            self::assertSame($oldHash, $pdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn());
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testRehashPolicyDoesNotDowngradeStrongerBcrypt(): void
    {
        $hash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 14]);
        self::assertFalse(\SP\Domain\Crypt\Services\Hash::needsRehash($hash));
    }

    #[\PHPUnit\Framework\Attributes\TestWith([false])]
    #[\PHPUnit\Framework\Attributes\TestWith([true])]
    #[Test]
    public function testRehashDoesNotOverwriteAConcurrentHashUpdate(bool $samePassword): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $oldHash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 4]);
        $competingHash = password_hash($samePassword ? 'synthetic' : 'new synthetic', PASSWORD_BCRYPT, ['cost' => 5]);
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $writer = new PDO('mysql:host=sp-masterpass-verify-db;dbname=' . $dbName, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $calls = 0;
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnCallback(static function () use ($pdo, $writer, $competingHash, &$calls): PDO {
            if ($calls++ === 0) {
                return $pdo;
            }
            $update = $writer->prepare("UPDATE Config SET value = ? WHERE parameter = 'masterPwd'");
            $update->execute([$competingHash]);
            return $writer;
        });
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            if ($samePassword) {
                $lock->assertCurrentMasterPassword('synthetic');
            } else {
                try {
                    $lock->assertCurrentMasterPassword('synthetic');
                    self::fail('A different winning password must invalidate this request');
                } catch (\SP\Domain\Common\Services\ServiceException $e) {
                    self::assertSame(\SP\Domain\Crypt\Ports\MasterPasswordRequestLock::STALE_MASTER_PASSWORD, $e->getCode());
                }
            }
            self::assertSame($competingHash, $writer->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn());
            if ($samePassword) {
                $lock->assertCurrentMasterPassword('synthetic');
            } else {
                try {
                    $lock->assertCurrentMasterPassword('synthetic');
                    self::fail('A stale verification must not remain cached');
                } catch (\SP\Domain\Common\Services\ServiceException $e) {
                    self::assertSame(\SP\Domain\Crypt\Ports\MasterPasswordRequestLock::STALE_MASTER_PASSWORD, $e->getCode());
                }
            }
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testWrongKeyDoesNotTriggerRehash(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $oldHash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 4]);
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $wrapper = $this->createMock(PDOWrapper::class);
        $wrapper->expects(self::once())->method('build')->willReturn($pdo);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            try {
                $lock->assertCurrentMasterPassword('wrong synthetic');
                self::fail('A wrong password must fail verification');
            } catch (\SP\Domain\Common\Services\ServiceException) {
                self::assertSame($oldHash, $pdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn());
            }
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testVerifiedOldHashIsRehashedWithoutChangingPasswordTimestamp(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $oldHash = password_hash('synthetic', PASSWORD_BCRYPT, ['cost' => 4]);
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $insert->execute(['lastupdatempass', '123']);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $writer = new PDO('mysql:host=sp-masterpass-verify-db;dbname=' . $dbName, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnOnConsecutiveCalls($pdo, $writer);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $lock->acquire();
        try {
            $lock->assertCurrentMasterPassword('synthetic');
            $hash = $writer->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
            self::assertNotSame($oldHash, $hash);
            self::assertTrue(\SP\Domain\Crypt\Services\Hash::checkMasterKey('synthetic', $hash));
            self::assertFalse(\SP\Domain\Crypt\Services\Hash::needsRehash($hash));
            self::assertSame('123', $writer->query("SELECT value FROM Config WHERE parameter = 'lastupdatempass'")->fetchColumn());
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function testRepeatedKeyValidationChecksLockLivenessWithoutRepeatingHashRead(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', \SP\Domain\Crypt\Services\Hash::hashKey('synthetic')]);
        $insert->execute(['masterPasswordActivityLock', '1']);
        $lock = new \SP\Infrastructure\Crypt\MasterPasswordActivityLock($pdo);
        $lock->acquireShared();
        try {
            $before = (int)$pdo->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch(PDO::FETCH_NUM)[1];
            $lock->assertCurrentMasterPassword('synthetic');
            $lock->assertCurrentMasterPassword('synthetic');
            $after = (int)$pdo->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch(PDO::FETCH_NUM)[1];
            self::assertSame(3, $after - $before);
        } finally {
            $lock->release();
        }
    }

    #[\PHPUnit\Framework\Attributes\TestWith([false])]
    #[\PHPUnit\Framework\Attributes\TestWith([true])]
    #[Test]
    public function testMissingLockRowAllowsOperatorManagedConfigTriggers(bool $rotation): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', \SP\Domain\Crypt\Services\Hash::hashKey('synthetic')]);
        $pdo->exec('CREATE TABLE side_effect (id INT) ENGINE=MyISAM');
        $pdo->exec('CREATE TRIGGER config_insert BEFORE INSERT ON Config FOR EACH ROW INSERT INTO side_effect VALUES (1)');
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturn($pdo);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        try {
            if ($rotation) {
                $storage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
                $storage->method('getConnection')->willReturn($pdo);
                $pdo->beginTransaction();
                (new \SP\Infrastructure\Crypt\MasterPasswordStorageGuard($storage))->lockAndVerifyCurrentPassword('synthetic');
            } else {
                $lock->acquire();
            }
        } finally {
            $lock->release();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM side_effect')->fetchColumn());
    }
}
