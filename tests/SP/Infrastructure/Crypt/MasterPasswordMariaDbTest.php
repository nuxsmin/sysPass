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

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\Crypt\MasterPasswordStorageGuard;
use SP\Infrastructure\Database\Database;
use SP\Tests\UnitaryTestCase;

/** Run only against the explicitly named disposable MariaDB test container. */
#[Group('integration')]
final class MasterPasswordMariaDbTest extends UnitaryTestCase
{
    use IsolatedMasterPasswordDatabase;

    #[Test]
    public function testRotationWithNoTemporarySettingsUsesRealConfigRepository(): void
    {
        $pdo = $this->isolatedDatabase();
        foreach (['Account', 'AccountHistory', 'CustomFieldData', 'PluginData', 'AuthToken'] as $table) {
            $pdo->exec("CREATE TABLE `$table` (id INT PRIMARY KEY, value VARCHAR(100)) ENGINE=InnoDB");
        }
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $oldHash = Hash::hashMasterKey('old synthetic');
        $pdo->prepare('INSERT INTO Config VALUES (?, ?), (?, ?)')->execute(['masterPwd', $oldHash, 'lastupdatempass', '1']);
        $pdo->exec("INSERT INTO Account VALUES (1, 'old')");
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $db = new Database($storage, $events);
        $queries = new QueryFactory('mysql');
        $repository = new SimpleRepository($db, $this->context, $events, $queries);
        $configRepo = new \SP\Infrastructure\Config\Repositories\Config($db, $this->context, $events, $queries);
        $config = new \SP\Domain\Config\Services\Config($this->config, $events, $this->context, $configRepo);
        $accounts = $this->createStub(AccountMasterPasswordService::class);
        $accounts->method('updateMasterPassword')->willReturnCallback(
            static fn() => $pdo->exec("UPDATE Account SET value = 'new' WHERE id = 1")
        );
        $service = new MasterPass($this->config, $events, $this->context, $config, $accounts,
            $this->createStub(CustomFieldCryptService::class), $repository, new MasterPasswordStorageGuard($storage));
        $service->changeMasterPassword(new UpdateMasterPassRequest('old synthetic', 'new synthetic', $oldHash));
        self::assertSame('new', $pdo->query('SELECT value FROM Account WHERE id = 1')->fetchColumn());
        $newHash = $pdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
        self::assertTrue(Hash::checkMasterKey('new synthetic', $newHash));
        self::assertSame(0, (int)$pdo->query("SELECT COUNT(*) FROM Config WHERE parameter LIKE 'tempmaster_%'")->fetchColumn());
    }

    #[Test]
    public function testNestedCompletionEventFailurePreservesOuterAtomicity(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE changes_test (value VARCHAR(100)) ENGINE=InnoDB');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $failed = false;
        $events->method('notify')->willReturnCallback(static function (string $name) use (&$failed): void {
            if ($name === 'database.transaction.end' && !$failed) {
                $failed = true;
                throw new \RuntimeException('synthetic notification failure');
            }
        });
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        try {
            $repository->transactionAware(function () use ($repository, $pdo): void {
                $pdo->exec("INSERT INTO changes_test VALUES ('outer')");
                try {
                    $repository->transactionAware(function () use ($pdo): void {
                        $pdo->exec("INSERT INTO changes_test VALUES ('inner')");
                    }, $this);
                    self::fail('Expected inner completion failure');
                } catch (ServiceException) {
                    self::assertTrue($pdo->inTransaction());
                    self::assertSame(['outer'], $pdo->query('SELECT value FROM changes_test')->fetchAll(PDO::FETCH_COLUMN));
                    $pdo->exec("INSERT INTO changes_test VALUES ('after failure')");
                }
                throw new \RuntimeException('outer failure');
            }, $this);
            self::fail('Expected outer failure');
        } catch (ServiceException) {
            self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM changes_test')->fetchColumn());
        }
    }

    #[Test]
    public function testStorageChecksAndAtomicRollback(): void
    {
        $pdo = $this->isolatedDatabase();
        foreach (['Account', 'AccountHistory', 'CustomFieldData', 'PluginData', 'AuthToken'] as $table) {
            $pdo->exec("CREATE TABLE `$table` (id INT PRIMARY KEY, value VARCHAR(100)) ENGINE=InnoDB");
        }
        $pdo->exec('CREATE TABLE Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $pdo->exec("INSERT INTO Account VALUES (1, 'old')");
        $oldHash = Hash::hashKey('old');
        $insert = $pdo->prepare('INSERT INTO Config VALUES (?, ?)');
        $insert->execute(['masterPwd', $oldHash]);
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $guard = new MasterPasswordStorageGuard($storage);
        $pdo->beginTransaction();
        try {
            $guard->assertTransactional();
        } finally {
            $pdo->rollBack();
        }
        $accounts = $this->createStub(AccountMasterPasswordService::class);
        $accounts->method('updateMasterPassword')->willReturnCallback(
            static fn() => $pdo->exec("UPDATE Account SET value = 'new' WHERE id = 1")
        );
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturnCallback(static function (string $key) use ($pdo): bool {
            if ($key === 'masterPwd') {
                $pdo->exec("UPDATE Config SET value = 'new' WHERE parameter = 'masterPwd'");
                return true;
            }
            return false;
        });
        $service = new MasterPass($this->config, $events, $this->context, $config, $accounts,
            $this->createStub(CustomFieldCryptService::class), $repository, $guard);
        try {
            $service->changeMasterPassword(new UpdateMasterPassRequest('old', 'new', 'hash'));
            self::fail('The failed configuration write must abort the change');
        } catch (ServiceException $e) {
            self::assertStringContainsString('Unable to update master password configuration', $e->getMessage());
            self::assertSame('old', $pdo->query('SELECT value FROM Account WHERE id = 1')->fetchColumn());
            self::assertSame($oldHash, $pdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn());
        }

        $pdo->exec("INSERT INTO PluginData VALUES (1, 'encrypted')");
        $pdo->beginTransaction();
        try {
            $this->expectException(ServiceException::class);
            $this->expectExceptionMessage('PluginData');
            $guard->assertTransactional();
        } finally {
            $pdo->rollBack();
        }
    }
}
