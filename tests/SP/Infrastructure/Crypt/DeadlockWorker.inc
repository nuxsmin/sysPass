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

$database = trim((string) fgets(STDIN));
$pdo = new PDO('mysql:host=' . getenv('DB_SERVER') . ';dbname=' . $database, 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->beginTransaction();
$pdo->exec('UPDATE deadlock_test SET value = value + 1 WHERE id = 2');
for ($id = 3; $id <= 30; $id++) {
    $pdo->exec('INSERT INTO deadlock_test VALUES (' . $id . ', 0)');
}
fwrite(STDOUT, "ready\n");
fflush(STDOUT);
fgets(STDIN);
$pdo->exec('UPDATE deadlock_test SET value = value + 1 WHERE id = 1');
$pdo->commit();
fwrite(STDOUT, "done\n");
