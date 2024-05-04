<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Infrastructure\Database;

use PDO;
use SP\Domain\Database\Ports\DbStorageHandler;

use function SP\__u;

/**
 * Class MySQLHandler
 */
final class MysqlHandler implements DbStorageHandler
{
    private const PDO_OPTS = [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ];

    private ?PDO            $pdo = null;
    private readonly string $connectionUri;

    public function __construct(
        private readonly DatabaseConnectionData $connectionData,
        private readonly PDOWrapper             $PDOWrapper
    ) {
        $this->connectionUri = $this->getConnectionUri();
    }

    private function getConnectionUri(): string
    {
        $dsn = ['charset=utf8'];

        if (empty($this->connectionData->getDbSocket())) {
            $dsn[] = sprintf('host=%s', $this->connectionData->getDbHost());

            if (null !== $this->connectionData->getDbPort()) {
                $dsn[] = sprintf('port=%s', $this->connectionData->getDbPort());
            }
        } else {
            $dsn[] = sprintf('unix_socket=%s', $this->connectionData->getDbSocket());
        }

        if (!empty($this->connectionData->getDbName())) {
            $dsn[] = sprintf('dbname=%s', $this->connectionData->getDbName());
        }

        return sprintf('mysql:%s', implode(';', $dsn));
    }

    /**
     * Set up a database connection with the given connection data.
     * This method will only set ATTR_EMULATE_PREPARES and ATTR_ERRMODE options.
     *
     * @throws DatabaseException
     */
    public function getConnectionSimple(): PDO
    {
        if (!$this->pdo) {
            $this->checkConnectionData();

            $opts = [
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];

            $this->pdo = $this->PDOWrapper->build($this->connectionUri, $this->connectionData, $opts);
        }

        return $this->pdo;
    }

    /**
     * @param bool $checkName
     * @return void
     * @throws DatabaseException
     */
    private function checkConnectionData(bool $checkName = false): void
    {
        $nameIsNotPresent = $checkName && null === $this->connectionData->getDbName();

        if ($nameIsNotPresent
            || null === $this->connectionData->getDbUser()
            || null === $this->connectionData->getDbPass()
            || (null === $this->connectionData->getDbHost()
                && null === $this->connectionData->getDbSocket())
        ) {
            throw DatabaseException::critical(
                __u('Unable to connect to DB'),
                __u('Please, check the connection parameters')
            );
        }
    }

    /**
     * @return DbStorageDriver
     */
    public function getDriver(): DbStorageDriver
    {
        return DbStorageDriver::mysql;
    }

    /**
     * Set up a database connection with the given connection data
     *
     * @throws DatabaseException
     */
    public function getConnection(): PDO
    {
        if (!$this->pdo) {
            $this->checkConnectionData(true);

            $this->pdo = $this->PDOWrapper->build($this->connectionUri, $this->connectionData, self::PDO_OPTS);

            // Set prepared statement emulation depending on server version
            $serverVersion = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $this->pdo->setAttribute(
                PDO::ATTR_EMULATE_PREPARES,
                version_compare($serverVersion, '5.1.17', '<')
            );
        }

        return $this->pdo;
    }
}
