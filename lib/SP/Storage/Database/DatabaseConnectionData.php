<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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
        return (new DatabaseConnectionData())
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
     * @return string|null
     */
    public function getDbHost(): ?string
    {
        return $this->dbHost;
    }

    /**
     * @param string $dbHost
     *
     * @return DatabaseConnectionData
     */
    public function setDbHost(string $dbHost): DatabaseConnectionData
    {
        $this->dbHost = $dbHost;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbSocket(): ?string
    {
        return $this->dbSocket;
    }

    /**
     * @param string|null $dbSocket
     *
     * @return DatabaseConnectionData
     */
    public function setDbSocket(?string $dbSocket): DatabaseConnectionData
    {
        $this->dbSocket = $dbSocket;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDbPort(): ?int
    {
        return $this->dbPort;
    }

    /**
     * @param int $dbPort
     *
     * @return DatabaseConnectionData
     */
    public function setDbPort(int $dbPort): DatabaseConnectionData
    {
        $this->dbPort = $dbPort;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    /**
     * @param string $dbName
     *
     * @return DatabaseConnectionData
     */
    public function setDbName(string $dbName): DatabaseConnectionData
    {
        $this->dbName = $dbName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbUser(): ?string
    {
        return $this->dbUser;
    }

    /**
     * @param string $dbUser
     *
     * @return DatabaseConnectionData
     */
    public function setDbUser(string $dbUser): DatabaseConnectionData
    {
        $this->dbUser = $dbUser;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbPass(): ?string
    {
        return $this->dbPass;
    }

    /**
     * @param string $dbPass
     *
     * @return DatabaseConnectionData
     */
    public function setDbPass(string $dbPass): DatabaseConnectionData
    {
        $this->dbPass = $dbPass;
        return $this;
    }
}