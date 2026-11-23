<?php

declare(strict_types=1);
/**
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

$host = getenv('SP_UI_DB_HOST') ?: 'db';
$port = (int)(getenv('SP_UI_DB_PORT') ?: 3306);
$user = getenv('SP_UI_DB_USER') ?: 'root';
$password = getenv('MARIADB_ROOT_PASSWORD');

if (!is_string($password) || $password === '') {
    fwrite(STDERR, "Database readiness probe has no credentials\n");

    exit(1);
}

try {
    $connection = new PDO(
        sprintf('mysql:host=%s;port=%d', $host, $port),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2,
        ]
    );

    $connection->query('SELECT 1');
} catch (PDOException $e) {
    fwrite(STDERR, "Database readiness probe failed over TCP\n");

    exit(1);
}

exit(0);