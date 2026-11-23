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

namespace SP\Tests\Modules\Cli;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PDO;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Modules\Cli\Init;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('integration')]
final class CliDatabaseAuditFlowTest extends CliTestCase
{
    #[Test]
    public function testCliInitializationRoutesRotationEventsToDatabase(): void
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $pdo = new PDO('mysql:host=sp-masterpass-verify-db;dbname=syspass_masterpass_test', 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE IF NOT EXISTS EventLog (id INT AUTO_INCREMENT PRIMARY KEY, date INT NOT NULL, login VARCHAR(25), userId INT NULL, ipAddress VARCHAR(45) NOT NULL, action VARCHAR(50) NOT NULL, description TEXT NULL, level VARCHAR(20) NOT NULL) ENGINE=InnoDB');
        $pdo->exec('DELETE FROM EventLog');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        self::$dic->set(DbStorageHandler::class, $storage);
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn(true);
        $configData->method('isLogEnabled')->willReturn(true);
        $configData->method('getLogEvents')->willReturn([]);
        $configData->method('getSiteLang')->willReturn('en_US');
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);
        self::$dic->set(ConfigDataInterface::class, $configData);
        self::$dic->set(ConfigFileService::class, $config);
        $repository = $this->createStub(Repository::class);
        $repository->method('transactionAware')->willReturnCallback(
            static function (\Closure $operation, object $service): void { $operation->call($service); }
        );
        $configService = $this->createStub(ConfigService::class);
        $configService->method('save')->willReturn(true);
        $oldHash = Hash::hashKey('old synthetic');
        $configService->method('getByParam')->willReturnCallback(
            static fn(string $key, mixed $default = null): mixed => $key === 'masterPwd' ? $oldHash : $default
        );
        $service = new MasterPass($config, self::$dic->get(EventDispatcherInterface::class), self::$dic->get(Context::class),
            $configService, $this->createStub(AccountMasterPasswordService::class), $this->createStub(CustomFieldCryptService::class),
            $repository, $this->createStub(MasterPasswordStorageGuard::class));
        self::$dic->set(ConfigService::class, $configService);
        self::$dic->set(MasterPassService::class, $service);
        self::$dic->set(AccountService::class, $this->createStub(AccountService::class));
        self::$dic->set(InputInterface::class, new ArrayInput([
            'command' => 'sp:crypt:update-master-password',
            '--currentMasterPassword' => 'old synthetic',
            '--masterPassword' => 'new synthetic',
            '--update' => true,
            '--acknowledge-backup' => true,
        ]));
        self::$dic->set(OutputInterface::class, new NullOutput());
        self::$dic->get(Application::class)->setAutoExit(false);
        self::$dic->get(Init::class)->initialize('');
        self::assertSame(['update.masterPassword.start', 'update.masterPassword.end'],
            $pdo->query('SELECT action FROM EventLog ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    }
}
