<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Storage\Database;

use SP\Config\ConfigData;

/**
 * Class DatabaseConnectionData
 *
 * @package SP\Storage
 */
final class DatabaseConnectionData
{
    /**
     * @var string
     */
    private $dbHost;
    /**
     * @var string
     */
    private $dbSocket;
    /**
     * @var int
     */
    private $dbPort;
    /**
     * @var string
     */
    private $dbName;
    /**
     * @var string
     */
    private $dbUser;
    /**
     * @var string
     */
    private $dbPass;

    /**
     * @param ConfigData $configData
     *
     * @return mixed
     */
    public static function getFromConfig(ConfigData $configData)
    {
        return (new static())
            ->setDbHost($configData->getDbHost())
            ->setDbName($configData->getDbName())
            ->setDbUser($configData->getDbUser())
            ->setDbPass($configData->getDbPass())
            ->setDbPort($configData->getDbPort())
            ->setDbSocket($configData->getDbSocket());
    }

    /**
     * @param ConfigData $configData
     *
     * @return DatabaseConnectionData
     */
    public function refreshFromConfig(ConfigData $configData)
    {
        logger('Refresh DB connection data');

        return $this->setDbHost($configData->getDbHost())
            ->setDbName($configData->getDbName())
            ->setDbUser($configData->getDbUser())
            ->setDbPass($configData->getDbPass())
            ->setDbPort($configData->getDbPort())
            ->setDbSocket($configData->getDbSocket());
    }

    /**
     * @return string
     */
    public function getDbHost()
    {
        return $this->dbHost;
    }

    /**
     * @param string $dbHost
     *
     * @return DatabaseConnectionData
     */
    public function setDbHost($dbHost)
    {
        $this->dbHost = $dbHost;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbSocket()
    {
        return $this->dbSocket;
    }

    /**
     * @param string $dbSocket
     *
     * @return DatabaseConnectionData
     */
    public function setDbSocket($dbSocket)
    {
        $this->dbSocket = $dbSocket;
        return $this;
    }

    /**
     * @return int
     */
    public function getDbPort()
    {
        return $this->dbPort;
    }

    /**
     * @param int $dbPort
     *
     * @return DatabaseConnectionData
     */
    public function setDbPort($dbPort)
    {
        $this->dbPort = $dbPort;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @param string $dbName
     *
     * @return DatabaseConnectionData
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbUser()
    {
        return $this->dbUser;
    }

    /**
     * @param string $dbUser
     *
     * @return DatabaseConnectionData
     */
    public function setDbUser($dbUser)
    {
        $this->dbUser = $dbUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbPass()
    {
        return $this->dbPass;
    }

    /**
     * @param string $dbPass
     *
     * @return DatabaseConnectionData
     */
    public function setDbPass($dbPass)
    {
        $this->dbPass = $dbPass;
        return $this;
    }
}