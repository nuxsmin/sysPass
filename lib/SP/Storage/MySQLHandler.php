<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Storage;

use PDO;
use SP\Core\Exceptions\SPException;

defined('APP_ROOT') || die();

/**
 * Class MySQLHandler
 *
 * Esta clase se encarga de crear las conexiones a la BD
 */
class MySQLHandler implements DBStorageInterface
{
    const STATUS_OK = 0;
    const STATUS_KO = 1;
    const PDO_OPTS = [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_FOUND_ROWS => true
    ];
    /**
     * @var PDO
     */
    private $db;
    /**
     * @var int
     */
    private $dbStatus = self::STATUS_KO;
    /**
     * @var DatabaseConnectionData
     */
    private $connectionData;

    /**
     * MySQLHandler constructor.
     *
     * @param DatabaseConnectionData $connectionData
     */
    public function __construct(DatabaseConnectionData $connectionData)
    {
        $this->connectionData = $connectionData;
    }

    /**
     * Devuelve el estado de conexión a la BBDD
     * OK -> 0
     * KO -> 1
     *
     * @return int
     */
    public function getDbStatus()
    {
        return $this->dbStatus;
    }

    /**
     * Realizar la conexión con la BBDD.
     * Esta función utiliza PDO para conectar con la base de datos.
     *
     * @throws \SP\Core\Exceptions\SPException
     * @return PDO
     */
    public function getConnection()
    {
        if (!$this->db) {
            if (null === $this->connectionData->getDbUser()
                || null === $this->connectionData->getDbPass()
                || null === $this->connectionData->getDbName()
                || (null === $this->connectionData->getDbHost() && null === $this->connectionData->getDbSocket())
            ) {
                throw new SPException(
                    __u('No es posible conectar con la BD'),
                    SPException::CRITICAL,
                    __u('Compruebe los datos de conexión'));
            }

            try {
                $this->db = new PDO(
                    $this->getConnectionUri(),
                    $this->connectionData->getDbUser(),
                    $this->connectionData->getDbPass(),
                    self::PDO_OPTS
                );

                // Set prepared statement emulation depending on server version
                $serverVersion = $this->db->getAttribute(PDO::ATTR_SERVER_VERSION);
                $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, version_compare($serverVersion, '5.1.17', '<'));

                $this->dbStatus = self::STATUS_OK;
            } catch (\Exception $e) {
                throw new SPException(
                    __u('No es posible conectar con la BD'),
                    SPException::CRITICAL,
                    sprintf('Error %s: %s', $e->getCode(), $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }
        }

        return $this->db;
    }

    /**
     * @return string
     */
    public function getConnectionUri()
    {
        if (empty($this->connectionData->getDbSocket())) {
            $dsn = 'mysql:host=' . $this->connectionData->getDbHost();

            if (null !== $this->connectionData->getDbPort()) {
                $dsn .= ';port=' . $this->connectionData->getDbPort();
            }

            if (null !== $this->connectionData->getDbName()) {
                $dsn .= ';dbname=' . $this->connectionData->getDbName();
            }

            return $dsn . ';charset=utf8';
        }

        $dsn = 'mysql:unix_socket=' . $this->connectionData->getDbSocket();

        if (!empty($this->connectionData->getDbName())) {
            $dsn .= ';dbname=' . $this->connectionData->getDbName();
        }

        return $dsn . ';charset=utf8';
    }

    /**
     * Obtener una conexión PDO sin seleccionar la BD
     *
     * @return \PDO
     * @throws SPException
     */
    public function getConnectionSimple()
    {
        if (!$this->db) {
            if (null === $this->connectionData->getDbHost() && null === $this->connectionData->getDbSocket()) {
                throw new SPException(
                    __u('No es posible conectar con la BD'),
                    SPException::CRITICAL,
                    __u('Compruebe los datos de conexión'));
            }

            try {
                $opts = [PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

                $this->db = new PDO($this->getConnectionUri(), $this->connectionData->getDbUser(), $this->connectionData->getDbPass(), $opts);
                $this->dbStatus = self::STATUS_OK;
            } catch (\Exception $e) {
                throw new SPException(
                    __u('No es posible conectar con la BD'),
                    SPException::CRITICAL,
                    sprintf('Error %s: %s', $e->getCode(), $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }
        }

        return $this->db;
    }
}