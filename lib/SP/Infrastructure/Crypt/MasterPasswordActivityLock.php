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
use RuntimeException;
use Throwable;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Services\Hash;

/** Holds a request-scoped lock on a dedicated connection. */
final class MasterPasswordActivityLock
{
    private bool $held = false;
    private ?string $verificationKey = null;
    private ?string $verifiedPasswordDigest = null;

    public function __construct(private readonly PDO $connection)
    {
    }

    public function acquireShared(): void
    {
        $this->acquire('LOCK IN SHARE MODE');
    }

    public function acquireExclusive(): void
    {
        $this->acquire('FOR UPDATE');
    }

    public function upgradeToExclusive(): void
    {
        if (!$this->held || !$this->connection->inTransaction()) {
            throw ServiceException::error('Master password activity lock is not held');
        }
        $this->verificationKey = null;
        $this->verifiedPasswordDigest = null;
        $stmt = $this->connection->prepare('SELECT value FROM Config WHERE parameter = ? FOR UPDATE');
        $stmt->execute(['masterPasswordActivityLock']);
        if ($stmt->fetchColumn() === false) {
            throw ServiceException::error('Master password activity lock is unavailable');
        }
    }

    public function assertCurrentMasterPassword(string $password): ?string
    {
        $this->assertLive();
        if ($this->verificationKey !== null && $this->verifiedPasswordDigest !== null
            && hash_equals($this->verifiedPasswordDigest, hash_hmac('sha256', $password, $this->verificationKey, true))) {
            return null;
        }
        $stmt = $this->connection->prepare('SELECT value FROM Config WHERE parameter = ?');
        $stmt->execute(['masterPwd']);
        $hash = $stmt->fetchColumn();
        if (!is_string($hash) || !Hash::checkMasterKey($password, $hash)) {
            throw ServiceException::error(
                'The master password has changed; restart the session',
                null,
                \SP\Domain\Crypt\Ports\MasterPasswordRequestLock::STALE_MASTER_PASSWORD
            );
        }
        $this->verificationKey = random_bytes(32);
        $this->verifiedPasswordDigest = hash_hmac('sha256', $password, $this->verificationKey, true);
        return $hash;
    }

    public function protectWrite(PDO $connection): void
    {
        $this->assertLive();
        if (!$connection->inTransaction()) {
            throw ServiceException::error('Master password write protection requires a transaction');
        }
        $activity = $connection->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' LOCK IN SHARE MODE");
        if ($activity->fetchColumn() === false) {
            throw ServiceException::error('Master password activity lock is unavailable');
        }
        // The request lock and write-transaction lock must overlap. If the
        // first one died before the second was acquired, do not write even if
        // the write connection now sees a newer rotation.
        $this->assertLive();
    }

    private function assertLive(): void
    {
        if (!$this->held || !$this->connection->inTransaction()) {
            throw ServiceException::error('Master password activity lock is not held');
        }
        $statement = $this->connection->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' LOCK IN SHARE MODE");
        // A server-side disconnect can change PDO's transaction state during query().
        // @phpstan-ignore booleanNot.alwaysFalse
        if ($statement->fetchColumn() === false || !$this->connection->inTransaction()) {
            throw ServiceException::error('Master password activity lock is unavailable');
        }
    }

    private function acquire(string $mode): void
    {
        if ($this->held || $this->connection->inTransaction()) {
            throw new RuntimeException('A dedicated connection without a transaction is required');
        }
        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare('SELECT value FROM Config WHERE parameter = ? ' . $mode);
            $stmt->execute(['masterPasswordActivityLock']);
            if ($stmt->fetchColumn() === false) {
                throw new RuntimeException('Master password activity lock is not configured');
            }
            $this->held = true;
        } catch (Throwable $e) {
            // PDO can leave the transaction after a server-side rollback.
            // @phpstan-ignore if.alwaysFalse
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    public function release(): void
    {
        try {
            if ($this->held && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
        } finally {
            $this->held = false;
            $this->verificationKey = null;
            $this->verifiedPasswordDigest = null;
        }
    }

    public function __destruct()
    {
        try {
            $this->release();
        } catch (Throwable) {
            \SP\logger('Unable to release master password activity lock', 'WARNING');
        }
    }
}
