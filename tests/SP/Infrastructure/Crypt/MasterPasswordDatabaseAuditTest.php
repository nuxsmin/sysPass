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
use PDO;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Core\Messages\EventMessageFormatterInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Domain\Security\Services\Eventlog;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Security\Repositories\Eventlog as EventlogRepository;
use SP\Tests\UnitaryTestCase;

#[Group('integration')]
final class MasterPasswordDatabaseAuditTest extends UnitaryTestCase
{
    use IsolatedMasterPasswordDatabase;

    #[Test]
    public function testRotationWritesStartAndCompletionToDatabaseAuditLog(): void
    {
        $pdo = $this->isolatedDatabase();
        $pdo->exec('CREATE TABLE EventLog (id INT AUTO_INCREMENT PRIMARY KEY, date INT NOT NULL, login VARCHAR(25), userId INT NULL, ipAddress VARCHAR(45) NOT NULL, action VARCHAR(50) NOT NULL, description TEXT NULL, level VARCHAR(20) NOT NULL) ENGINE=InnoDB');
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        $events = new EventDispatcher();
        $database = new Database($storage, $events);
        $queries = new QueryFactory('mysql');
        $request = $this->createStub(RequestService::class);
        $request->method('getClientAddress')->willReturn('127.0.0.1');
        $eventlog = new Eventlog($this->config, $events, $this->context,
            new EventlogRepository($database, $this->context, $events, $queries), $request);
        $handler = new DatabaseHandler($this->config, $events, $this->context, $eventlog,
            $this->createStub(LanguageInterface::class), $this->createStub(EventMessageFormatterInterface::class));
        $events->attach($handler);
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturn(true);
        $service = new MasterPass($this->config, $events, $this->context, $config,
            $this->createStub(AccountMasterPasswordService::class), $this->createStub(CustomFieldCryptService::class),
            new SimpleRepository($database, $this->context, $events, $queries), $this->createStub(MasterPasswordStorageGuard::class));
        $service->changeMasterPassword(new UpdateMasterPassRequest('old synthetic', 'new synthetic', 'hash'));
        self::assertSame(['update.masterPassword.start', 'update.masterPassword.end'],
            $pdo->query('SELECT action FROM EventLog ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    }
}
