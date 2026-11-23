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

use PDO;

/** A private schema on the explicitly designated disposable test server. */
trait IsolatedMasterPasswordDatabase
{
    private ?PDO $isolatedConnection = null;
    private ?string $isolatedDatabase = null;

    private function isolatedDatabase(): PDO
    {
        if (getenv('DB_SERVER') !== 'sp-masterpass-verify-db' || getenv('DB_NAME') !== 'syspass_masterpass_test') {
            self::markTestSkipped('Requires the disposable master password verification database');
        }
        $this->isolatedConnection = new PDO('mysql:host=sp-masterpass-verify-db', 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->isolatedDatabase = 'sp_masterpass_' . bin2hex(random_bytes(8));
        $this->isolatedConnection->exec('CREATE DATABASE `' . $this->isolatedDatabase . '`');
        $this->isolatedConnection->exec('USE `' . $this->isolatedDatabase . '`');

        return $this->isolatedConnection;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->isolatedConnection !== null && $this->isolatedDatabase !== null) {
                $this->isolatedConnection->exec('DROP DATABASE IF EXISTS `' . $this->isolatedDatabase . '`');
            }
        } finally {
            parent::tearDown();
        }
    }
}
