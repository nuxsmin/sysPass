<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use SP\Storage\Database\DatabaseException;

/**
 * Class DatabaseBaseTest
 *
 * Caso de test para tests que requieran consultas a la BBDD
 *
 * @package SP\Tests
 */
abstract class DatabaseTestCase extends TestCase
{
    /**
     * @var bool
     */
    protected static $loadFixtures = false;
    /**
     * @var PDO
     */
    private static $conn;

    /**
     * @param string $table
     *
     * @return int
     */
    protected static function getRowCount(string $table): int
    {
        if (!self::$conn) {
            return 0;
        }

        $sql = sprintf('SELECT count(*) FROM `%s`', $table);

        return (int)self::$conn->query($sql)->fetchColumn();
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$loadFixtures) {
            self::loadFixtures();
        }
    }

    protected static function loadFixtures()
    {
        $dbServer = getenv('DB_SERVER');
        $dbUser = getenv('DB_USER');
        $dbPass = getenv('DB_PASS');
        $dbName = getenv('DB_NAME');

        foreach (FIXTURE_FILES as $file) {
            if (!empty($dbPass)) {
                $cmd = sprintf(
                    'mysql -h %s -u %s -p%s %s < %s',
                    $dbServer,
                    $dbUser,
                    $dbPass,
                    $dbName,
                    $file
                );
            } else {
                $cmd = sprintf(
                    'mysql -h %s -u %s %s < %s',
                    $dbServer,
                    $dbUser,
                    $dbName,
                    $file
                );
            }

            exec($cmd, $output, $res);

            if ($res !== 0) {
                error_log(sprintf('Cannot load fixtures from: %s', $file));
                error_log(sprintf('CMD: %s', $cmd));
                error_log(print_r($output, true));

                exit(1);
            }

            printf('Fixtures loaded from: %s' . PHP_EOL, $file);
        }

        if (!self::$conn) {
            try {
                self::$conn = getDbHandler()->getConnection();
            } catch (DatabaseException $e) {
                processException($e);

                exit(1);
            }
        }
    }


}