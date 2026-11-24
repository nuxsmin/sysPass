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

namespace SP\Infrastructure\Crypt;

use PDO;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard as MasterPasswordStorageGuardPort;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Database\Ports\DbStorageHandler;

final class MasterPasswordStorageGuard implements MasterPasswordStorageGuardPort
{
    public function __construct(private readonly DbStorageHandler $storage)
    {
    }

    public function lockAndVerifyCurrentPassword(string $password): void
    {
        $connection = $this->storage->getConnection();
        if (!$connection->inTransaction()) {
            throw ServiceException::error('Master password verification requires a transaction');
        }
        // The coordination row is distinct from masterPwd so request-scoped
        // shared locks do not block the application's own configuration writes.
        try {
            MasterPasswordLockRow::ensure($connection);
            $activity = $connection->prepare('SELECT value FROM Config WHERE parameter = ? FOR UPDATE');
            $activity->execute(['masterPasswordActivityLock']);
            if ($activity->fetchColumn() === false) {
                throw ServiceException::error('Master password activity lock is unavailable');
            }
            $statement = $connection->prepare('SELECT value FROM Config WHERE parameter = ? FOR UPDATE');
            $statement->execute(['masterPwd']);
            $hash = $statement->fetchColumn();
        } catch (\PDOException $e) {
            if (in_array($e->errorInfo[1] ?? null, [1205, 1213], true)) {
                throw ServiceException::error('Master password activity is busy; retry after other requests finish', null, 0, $e);
            }
            throw $e;
        }
        if (!is_string($hash) || !Hash::checkMasterKey($password, $hash)) {
            throw ServiceException::error('The current master password does not match');
        }
    }

    public function assertTransactional(): void
    {
        $connection = $this->storage->getConnection();
        if (!$connection->inTransaction()) {
            throw ServiceException::error('Master password storage verification requires a transaction');
        }
        // Current reads take transaction-duration metadata locks without creating
        // a repeatable-read snapshot before rotation starts.
        foreach (['Account', 'AccountHistory', 'CustomFieldData', 'Config', 'PluginData', 'AuthToken'] as $table) {
            $connection->query("SELECT 1 FROM `$table` LIMIT 1 FOR UPDATE");
        }
        $statement = $connection->query("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('Account', 'AccountHistory', 'CustomFieldData', 'Config', 'PluginData', 'AuthToken')");
        $engines = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach (['Account', 'AccountHistory', 'CustomFieldData', 'Config', 'PluginData', 'AuthToken'] as $table) {
            if (!isset($engines[$table]) || strcasecmp((string)$engines[$table], 'InnoDB') !== 0) {
                throw ServiceException::error('Master password change requires InnoDB storage for all affected tables');
            }
        }

        foreach (['PluginData', 'AuthToken'] as $table) {
            if ($connection->query("SELECT 1 FROM `$table` LIMIT 1 FOR UPDATE")->fetchColumn() !== false) {
                throw ServiceException::error("Master password change cannot run while $table records exist; see the recovery documentation");
            }
        }
    }
}
