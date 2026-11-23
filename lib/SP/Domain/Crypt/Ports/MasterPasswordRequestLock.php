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

namespace SP\Domain\Crypt\Ports;

use PDO;

interface MasterPasswordRequestLock
{
    public const STALE_MASTER_PASSWORD = 71301;

    public function acquire(): void;

    /** Upgrade the request's shared lock before changing the master password hash. */
    public function upgradeToExclusive(): void;

    public function assertCurrentMasterPassword(string $password): void;

    /** Whether this entry point owns a request-scoped lock. */
    public function isActive(): bool;

    /** Fence a write on its own transaction connection. */
    public function protectWrite(PDO $connection): void;

    public function release(): void;
}
