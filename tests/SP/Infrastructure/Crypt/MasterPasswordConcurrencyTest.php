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
use Aura\SqlQuery\QueryFactory;
use PDO;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Crypt\MasterPasswordStorageGuard as StorageGuard;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Infrastructure\Crypt\MasterPasswordRequestLock;
use SP\Infrastructure\Database\PDOWrapper;
use SP\Tests\UnitaryTestCase;

/** Requires a disposable server, never an existing application database. */
#[Group('integration')]
final class MasterPasswordConcurrencyTest extends UnitaryTestCase
{
    #[Test]
    public function testCompetingRotationsSerializeAndRejectStalePassword(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $first = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $second = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $first->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $insert = $first->prepare('INSERT INTO Config VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $insert->execute(['masterPwd', Hash::hashKey('old synthetic')]);
        $storage1 = $this->createStub(DbStorageHandler::class);
        $storage1->method('getConnection')->willReturn($first);
        $storage2 = $this->createStub(DbStorageHandler::class);
        $storage2->method('getConnection')->willReturn($second);
        $guard1 = new StorageGuard($storage1);
        $guard2 = new StorageGuard($storage2);
        $second->exec('SET innodb_lock_wait_timeout = 1');
        $first->beginTransaction();
        $guard1->lockAndVerifyCurrentPassword('old synthetic');
        $second->beginTransaction();
        try {
            $guard2->lockAndVerifyCurrentPassword('old synthetic');
            self::fail('The second rotation must wait for the first');
        } catch (ServiceException) {
            self::assertTrue($first->inTransaction());
        } finally {
            $second->rollBack();
        }
        $update = $first->prepare('UPDATE Config SET value = ? WHERE parameter = ?');
        $update->execute([Hash::hashKey('new synthetic'), 'masterPwd']);
        $first->commit();
        $second->beginTransaction();
        try {
            $guard2->lockAndVerifyCurrentPassword('old synthetic');
            self::fail('The stale password must be rejected after the first rotation');
        } catch (ServiceException) {
            self::assertTrue($second->inTransaction());
        } finally {
            $second->rollBack();
        }
    }

    #[Test]
    public function testConcurrentOldKeyWriteDoesNotSurviveSuccessfulRotation(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $dsn = 'mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test';
        $first = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $second = new PDO($dsn, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $first->exec('CREATE TABLE IF NOT EXISTS Config (parameter VARCHAR(50) PRIMARY KEY, value VARCHAR(255)) ENGINE=InnoDB');
        $insertConfig = $first->prepare('INSERT INTO Config VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $insertConfig->execute(['masterPwd', Hash::hashKey('old synthetic')]);
        $first->exec('CREATE TABLE IF NOT EXISTS concurrency_account (id INT PRIMARY KEY, key_version VARCHAR(8)) ENGINE=InnoDB');
        $first->exec('DELETE FROM concurrency_account');
        $first->exec("INSERT INTO concurrency_account VALUES (1, 'old')");
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($first);
        $events = $this->createStub(EventDispatcherInterface::class);
        $repository = new SimpleRepository(new Database($storage, $events), $this->context, $events, new QueryFactory('mysql'));
        $second->exec('SET innodb_lock_wait_timeout = 1');
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturn($second);
        $requestLock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $blocked = false;
        $accounts = $this->createStub(AccountMasterPasswordService::class);
        $accounts->method('updateMasterPassword')->willReturnCallback(static function () use ($first, $requestLock, &$blocked): void {
            $rows = $first->query('SELECT id FROM concurrency_account ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
            try {
                $requestLock->acquire();
            } catch (\PDOException) {
                $blocked = true;
            }
            $update = $first->prepare("UPDATE concurrency_account SET key_version = 'new' WHERE id = ?");
            foreach ($rows as $id) {
                $update->execute([$id]);
            }
        });
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturnCallback(static function (string $key, string $value) use ($first): bool {
            if ($key === 'masterPwd') {
                $stmt = $first->prepare('UPDATE Config SET value = ? WHERE parameter = ?');
                $stmt->execute([$value, $key]);
            }
            return true;
        });
        $guard = $this->createStub(MasterPasswordStorageGuard::class);
        $guard->method('lockAndVerifyCurrentPassword')->willReturnCallback(
            static fn(string $password) => (new StorageGuard($storage))->lockAndVerifyCurrentPassword($password)
        );
        $service = new MasterPass($this->config, $events, $this->context, $config, $accounts,
            $this->createStub(CustomFieldCryptService::class), $repository, $guard);
        $service->changeMasterPassword(new UpdateMasterPassRequest('old synthetic', 'new synthetic', 'hash'));
        self::assertTrue($blocked, 'The competing request must not pass the gate while rotation holds it');
        self::assertSame(0, (int)$second->query("SELECT COUNT(*) FROM concurrency_account WHERE key_version = 'old'")->fetchColumn());
        $requestLock->acquire();
        try {
            $requestLock->assertCurrentMasterPassword('old synthetic');
            self::fail('An old key must be rejected after rotation');
        } catch (ServiceException) {
            $requestLock->assertCurrentMasterPassword('new synthetic');
        } finally {
            $requestLock->release();
        }
    }
}
