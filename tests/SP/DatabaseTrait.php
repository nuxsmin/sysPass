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

namespace SP\Tests;

use PDO;
use SP\Domain\Core\Exceptions\DatabaseException;

/**
 *
 */
trait DatabaseTrait
{
    protected static bool $loadFixtures = false;
    private static ?PDO $conn = null;

    protected static function getRowCount(string $table): int
    {
        if (!self::$conn) {
            self::setConnection();
        }

        $sql = sprintf('SELECT count(*) FROM `%s`', $table);

        return (int)self::$conn->query($sql)->fetchColumn();
    }

    protected static function fetchValue(string $sql): mixed
    {
        if (!self::$conn) {
            self::setConnection();
        }

        return self::$conn->query($sql)->fetchColumn();
    }

    protected static function setConnection(): void
    {
        if (!self::$conn) {
            try {
                self::$conn = getDbHandler()->getConnection();
            } catch (DatabaseException $e) {
                processException($e);

                exit(1);
            }
        }
    }

    protected static function loadFixtures(): void
    {
        // In-process requests can leave cyclic containers holding request-scoped
        // database locks until PHP collects them. Release them before TRUNCATE.
        gc_collect_cycles();

        $dbServer = getenv('DB_SERVER');
        $dbUser = getenv('DB_USER');
        $dbPass = getenv('DB_PASS');
        $dbName = getenv('DB_NAME');

        foreach (FIXTURE_FILES as $file) {
            if (!is_readable($file)) {
                error_log(sprintf('Cannot read fixtures from: %s', $file));

                exit(1);
            }

            $cmd = ['mysql', '-h', (string)$dbServer, '-u', (string)$dbUser, (string)$dbName];
            $environment = getenv();
            $environment['MYSQL_PWD'] = (string)$dbPass;

            $process = proc_open(
                $cmd,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ],
                $pipes,
                null,
                $environment
            );

            if (is_resource($process) === false) {
                error_log(sprintf('Cannot run command: %s', $cmd));

                exit(1);
            }

            fwrite($pipes[0], file_get_contents($file) ?: '');
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $res = proc_close($process);

            if ($res !== 0) {
                /** @noinspection ForgottenDebugOutputInspection */
                error_log(sprintf('Cannot load fixtures from: %s', $file));
                /** @noinspection ForgottenDebugOutputInspection */
                error_log('Failed to execute mysql fixture loader');
                /** @noinspection ForgottenDebugOutputInspection */
                error_log($stderr ?: $stdout);

                exit(1);
            }

            printf('Fixtures loaded from: %s' . PHP_EOL, $file);
        }
    }

    protected static function truncateTable(string $table): void
    {
        if (!self::$conn) {
            self::setConnection();
        }

        $sql = sprintf('TRUNCATE TABLE `%s`', $table);

        self::$conn->exec($sql);
    }
}
