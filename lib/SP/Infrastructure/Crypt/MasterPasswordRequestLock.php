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
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock as MasterPasswordRequestLockPort;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\Crypt\Services\Hash;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\Database\PDOWrapper;

/** Owns a separate database connection for the lifetime of a request. */
final class MasterPasswordRequestLock implements MasterPasswordRequestLockPort
{
    private ?MasterPasswordActivityLock $lock = null;
    private bool $stale = false;

    public function __construct(
        private readonly ConfigDataInterface $config,
        private readonly PDOWrapper $pdoWrapper
    ) {
    }

    public function acquire(): void
    {
        if ($this->lock !== null) {
            throw ServiceException::error('Master password request lock is already held');
        }
        $data = DatabaseConnectionData::getFromConfig($this->config);
        $pdo = $this->pdoWrapper->build(MysqlHandler::getConnectionUri($data), $data, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        MasterPasswordLockRow::ensure($pdo);
        $lock = new MasterPasswordActivityLock($pdo);
        $lock->acquireShared();
        $this->lock = $lock;
    }

    public function assertCurrentMasterPassword(string $password): void
    {
        if ($this->lock === null) {
            throw ServiceException::error('Master password request lock is not held');
        }
        if ($this->stale) {
            throw ServiceException::error('The master password has changed; restart the session', null, self::STALE_MASTER_PASSWORD);
        }
        $hash = $this->lock->assertCurrentMasterPassword($password);
        if ($hash !== null && Hash::needsRehash($hash)) {
            // The request still holds its shared activity lock. A separate
            // autocommit connection avoids holding a write lock until request end.
            $pdo = null;
            try {
                $data = DatabaseConnectionData::getFromConfig($this->config);
                $pdo = $this->pdoWrapper->build(MysqlHandler::getConnectionUri($data), $data, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->beginTransaction();
                $current = $pdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd' FOR UPDATE")->fetchColumn();
                if (!is_string($current) || (!hash_equals($hash, $current) && !Hash::checkMasterKey($password, $current))) {
                    $this->stale = true;
                    throw ServiceException::error('The master password has changed; restart the session', null, self::STALE_MASTER_PASSWORD);
                }
                if (hash_equals($hash, $current)) {
                    MasterPasswordLockRow::assertSafeConfigWrite($pdo);
                    $statement = $pdo->prepare("UPDATE Config SET value = ? WHERE parameter = 'masterPwd' AND BINARY value = BINARY ?");
                    $current = Hash::hashMasterKey($password);
                    $statement->execute([$current, $hash]);
                    if ($statement->rowCount() !== 1) {
                        $this->stale = true;
                        throw ServiceException::error('The master password has changed; restart the session', null, self::STALE_MASTER_PASSWORD);
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                try {
                    if ($pdo !== null && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                } catch (\Throwable) {
                    // The failed rehash connection may already be unavailable.
                }
                if ($this->stale) {
                    throw $e;
                }
                \SP\logger('Unable to upgrade master password hash', 'WARNING');
            }
        }
    }

    public function upgradeToExclusive(): void
    {
        if ($this->lock === null) {
            throw ServiceException::error('Master password request lock is not held');
        }
        $this->lock->upgradeToExclusive();
    }

    public function isActive(): bool
    {
        return $this->lock !== null;
    }

    public function protectWrite(PDO $connection): void
    {
        if ($this->lock === null) {
            throw ServiceException::error('Master password request lock is not held');
        }
        if ($this->stale) {
            throw ServiceException::error('The master password has changed; restart the session', null, self::STALE_MASTER_PASSWORD);
        }
        $this->lock->protectWrite($connection);
    }

    public function release(): void
    {
        try {
            $this->lock?->release();
        } finally {
            $this->lock = null;
            $this->stale = false;
        }
    }

    public function __destruct()
    {
        try {
            $this->release();
        } catch (\Throwable) {
            \SP\logger('Unable to release master password request lock', 'WARNING');
        }
    }
}
