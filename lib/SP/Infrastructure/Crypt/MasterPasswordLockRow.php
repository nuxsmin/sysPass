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
use Throwable;

/** Initializes the coordination row only when its insert is known to be transactional. */
final class MasterPasswordLockRow
{
    public static function ensure(PDO $pdo): void
    {
        // A consistent read inside a rotation transaction would establish a
        // snapshot before competing requests have finished writing.
        $suffix = $pdo->inTransaction() ? ' FOR UPDATE' : '';
        $check = $pdo->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock'" . $suffix);
        if ($check->fetchColumn() !== false) {
            return;
        }

        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }
        try {
            // Serialize initialization and hold a metadata lock on Config until commit.
            if ($pdo->query("SELECT value FROM Config WHERE parameter = 'masterPwd' FOR UPDATE")->fetchColumn() === false) {
                throw ServiceException::error('Master password configuration is unavailable');
            }
            if ($pdo->query("SELECT value FROM Config WHERE parameter = 'masterPasswordActivityLock' FOR UPDATE")->fetchColumn() === false) {
                self::assertSafeConfigWrite($pdo);
                $pdo->exec("INSERT INTO Config (parameter, value) VALUES ('masterPasswordActivityLock', '1')");
            }
            if ($ownTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** The caller must already hold a transaction-level metadata lock on Config. */
    public static function assertSafeConfigWrite(PDO $pdo): void
    {
        $engine = $pdo->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Config'")->fetchColumn();
        if (!is_string($engine) || strcasecmp($engine, 'InnoDB') !== 0) {
            throw ServiceException::error('Master password configuration write requires InnoDB Config storage');
        }
    }
}
