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

namespace SP\Tests\Modules\Web\Controllers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SP\Core\Crypt\ValidatedMasterKeyStore;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock as LockPort;
use SP\Infrastructure\Crypt\MasterPasswordActivityLock;
use SP\Infrastructure\Crypt\MasterPasswordRequestLock;
use SP\Infrastructure\Database\PDOWrapper;
use SP\Tests\DatabaseIntegrationTestCase;

use function SP\Tests\getDbHandler;

#[RunTestsInSeparateProcesses]
#[Group('integration')]
final class MasterPasswordConcurrentWriteTest extends DatabaseIntegrationTestCase
{
    protected function setUp(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        parent::setUp();
    }

    #[Test]
    public function testWebAccountWriteHoldsLockUntilControllerCompletes(): void
    {
        $requestPdo = new PDO('mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test', 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rotationPdo = new PDO('mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test', 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rehashPdo = new PDO('mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test', 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $oldHash = password_hash('12345678900', PASSWORD_BCRYPT, ['cost' => 4]);
        $setHash = $rotationPdo->prepare("UPDATE Config SET value = ? WHERE parameter = 'masterPwd'");
        $setHash->execute([$oldHash]);
        $oldTimestamp = $rotationPdo->query("SELECT value FROM Config WHERE parameter = 'lastupdatempass'")->fetchColumn();
        $rotationPdo->exec('SET innodb_lock_wait_timeout = 1');
        $wrapper = $this->createStub(PDOWrapper::class);
        $wrapper->method('build')->willReturnOnConsecutiveCalls($requestPdo, $rehashPdo, $requestPdo);
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $wrapper);
        $masterKey = $this->createStub(MasterKeyStore::class);
        $masterKey->method('get')->willReturn('12345678900');
        $installed = $this->createConfiguredStub(ConfigDataInterface::class, ['isInstalled' => true]);
        $validatedMasterKey = new ValidatedMasterKeyStore($masterKey, $lock, $installed);
        $cliData = $this->createConfiguredStub(ConfigDataInterface::class, [
            'isInstalled' => true, 'isMaintenance' => true, 'isLogEnabled' => false,
            'isMailEnabled' => false, 'getLogEvents' => [], 'getSiteLang' => 'en_US',
        ]);
        $cliConfig = $this->createStub(\SP\Domain\Config\Ports\ConfigFileService::class);
        $cliConfig->method('getConfigData')->willReturn($cliData);
        $cliStorage = $this->createStub(\SP\Domain\Database\Ports\DbStorageHandler::class);
        $cliStorage->method('getConnection')->willReturn($rotationPdo);
        $probe = \SP\Tests\Infrastructure\Crypt\CliRotationProbe::build($cliStorage, $cliConfig, $cliData);
        $receiver = new class($rotationPdo, $probe) implements EventReceiver {
            public bool $blocked = false;
            public string $cliOutput = '';
            public float $cliDuration = 0;
            public function __construct(private PDO $pdo, private \Closure $probe) {}
            public function getEvents(): ?string { return '^create\.account$'; }
            public function update(string $eventType, Event $event): void
            {
                $start = microtime(true);
                try {
                    $this->cliOutput = ($this->probe)();
                } catch (\Throwable $e) {
                    $this->cliOutput = $e::class . ': ' . $e->getMessage();
                }
                $this->cliDuration = microtime(true) - $start;
                $lock = new MasterPasswordActivityLock($this->pdo);
                try {
                    $lock->acquireExclusive();
                } catch (PDOException $exception) {
                    $this->blocked = ($exception->errorInfo[1] ?? null) === 1205;
                } finally {
                    $lock->release();
                }
            }
        };
        $events = new EventDispatcher();
        $events->attach($receiver);
        $errors = new class implements EventReceiver {
            public string $message = '';
            public function getEvents(): ?string { return '^exception$'; }
            public function update(string $eventType, Event $event): void {
                $source = $event->getSource();
                if ($source instanceof \Throwable) { $this->message = $source::class . ': ' . $source->getMessage(); }
            }
        };
        $events->attach($errors);
        $this->db = new \SP\Infrastructure\Database\Database(getDbHandler(), $events, $lock);
        try {
            $response = $this->dispatch('post', 'account/saveCreate', [], [
                'name' => 'Concurrent Web Account', 'login' => 'synthetic',
                'client_id' => 1, 'category_id' => 1, 'owner_id' => 1,
                'main_usergroup_id' => 1, 'password' => 'synthetic-secret',
                'password_repeat' => 'synthetic-secret', 'parent_account_id' => 0,
            ], [LockPort::class => $lock, MasterKeyStore::class => $validatedMasterKey, EventDispatcherInterface::class => $events]);
            self::assertStringContainsString('ERROR', $receiver->cliOutput, $response['body'] . ' ' . $errors->message . ' CLI: ' . $receiver->cliOutput);
            self::assertStringContainsString('Master password activity is busy', $receiver->cliOutput);
            self::assertGreaterThanOrEqual(0.8, $receiver->cliDuration, 'The CLI should wait for the Web request lock');
            $this->assertJsonOk($response, 'Account created');
            self::assertTrue($receiver->blocked, 'Rotation must wait for the Web write to finish');
            self::assertStringNotContainsString('Master password updated', $receiver->cliOutput);
            $rehash = $rotationPdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd'")->fetchColumn();
            self::assertNotSame($oldHash, $rehash);
            self::assertFalse(\SP\Domain\Crypt\Services\Hash::needsRehash($rehash));
            self::assertSame($oldTimestamp, $rotationPdo->query("SELECT value FROM Config WHERE parameter = 'lastupdatempass'")->fetchColumn());
            self::assertSame(1, (int)getDbHandler()->getConnection()->query("SELECT COUNT(*) FROM Account WHERE name = 'Concurrent Web Account'")->fetchColumn());
        } finally {
            $lock->release();
        }
        $exclusive = new MasterPasswordActivityLock($rotationPdo);
        $exclusive->acquireExclusive();
        $exclusive->release();

        // Rotation refuses unsupported encrypted stores; clear the synthetic
        // fixtures before allowing the real command to complete.
        $rotationPdo->exec('DELETE FROM PluginData');
        $rotationPdo->exec('DELETE FROM AuthToken');
        $rotationOutput = $probe();
        self::assertStringContainsString('Master password updated', $rotationOutput, $rotationOutput);
        try {
            $stale = $this->dispatch('post', 'account/saveCreate', [], [
                'name' => 'Stale Web Account', 'login' => 'synthetic',
                'client_id' => 1, 'category_id' => 1, 'owner_id' => 1,
                'main_usergroup_id' => 1, 'password' => 'synthetic-secret',
                'password_repeat' => 'synthetic-secret', 'parent_account_id' => 0,
            ], [LockPort::class => $lock, MasterKeyStore::class => $validatedMasterKey]);
            self::assertStringContainsString('ERROR', $stale['body']);
            self::assertStringContainsString('The master password has changed', $stale['body']);
            self::assertSame(0, (int)$rotationPdo->query("SELECT COUNT(*) FROM Account WHERE name = 'Stale Web Account'")->fetchColumn());
        } finally {
            $lock->release();
        }
    }
}
