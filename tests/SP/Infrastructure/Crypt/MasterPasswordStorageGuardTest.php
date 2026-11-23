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
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Infrastructure\Crypt\MasterPasswordStorageGuard;

#[Group('unitary')]
final class MasterPasswordStorageGuardTest extends TestCase
{
    public static function rejectedStorage(): array
    {
        return [
            'nontransactional table' => ['MyISAM', 1, [], false, false],
            'unverifiable triggers' => ['InnoDB', 0, [], false, false],
            'trigger present' => ['InnoDB', 1, [['Table' => 'Account']], false, false],
            'plugin data present' => ['InnoDB', 1, [], 1, false],
            'token present' => ['InnoDB', 1, [], false, 1],
        ];
    }

    #[DataProvider('rejectedStorage')]
    #[Test]
    public function testUnsafeStorageIsRejected(string $engine, int $privilege, array $triggers, int|false $plugin, int|false $token): void
    {
        $this->expectException(ServiceException::class);
        $this->guard($engine, $privilege, $triggers, $plugin, $token)->assertTransactional();
    }

    #[Test]
    public function testVerifiedEmptyTransactionalStorageIsAccepted(): void
    {
        $this->guard('InnoDB', 1, [], false, false)->assertTransactional();
        self::assertTrue(true);
    }

    private function guard(string $engine, int $privilege, array $triggers, int|false $plugin, int|false $token): MasterPasswordStorageGuard
    {
        $engines = $this->createStub(PDOStatement::class);
        $engines->method('fetchAll')->willReturn(array_fill_keys(
            ['Account', 'AccountHistory', 'CustomFieldData', 'Config', 'PluginData', 'AuthToken'],
            $engine
        ));
        $privileges = $this->createStub(PDOStatement::class);
        $privileges->method('execute')->willReturn(true);
        $privileges->method('fetchColumn')->willReturn($privilege);
        $triggerResult = $this->createStub(PDOStatement::class);
        $triggerResult->method('fetchAll')->willReturn($triggers);
        $pluginResult = $this->createStub(PDOStatement::class);
        $pluginResult->method('fetchColumn')->willReturn($plugin);
        $tokenResult = $this->createStub(PDOStatement::class);
        $tokenResult->method('fetchColumn')->willReturn($token);
        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($privileges);
        $pdo->method('query')->willReturnCallback(static function (string $sql) use ($engines, $triggerResult, $pluginResult, $tokenResult): PDOStatement {
            return match (true) {
                str_contains($sql, 'information_schema.TABLES') => $engines,
                str_contains($sql, 'SHOW TRIGGERS') => $triggerResult,
                str_contains($sql, '`PluginData`') => $pluginResult,
                default => $tokenResult,
            };
        });
        $storage = $this->createStub(DbStorageHandler::class);
        $storage->method('getConnection')->willReturn($pdo);
        return new MasterPasswordStorageGuard($storage);
    }
}
