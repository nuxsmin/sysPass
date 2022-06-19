<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Config\In\ConfigDataInterface;

/**
 * Class DatabaseConnectionData
 *
 * @package SP\Infrastructure\Database
 */
final class DatabaseConnectionData
{
    private ?string $dbHost   = null;
    private ?string $dbSocket = null;
    private ?int    $dbPort   = null;
    private ?string $dbName   = null;
    private ?string $dbUser   = null;
    private ?string $dbPass   = null;

    public static function getFromConfig(ConfigDataInterface $configData): DatabaseConnectionData
    {
        return (new self())
            ->setDbHost($configData->getDbHost() ?? '')
            ->setDbName($configData->getDbName() ?? '')
            ->setDbUser($configData->getDbUser() ?? '')
            ->setDbPass($configData->getDbPass() ?? '')
            ->setDbPort($configData->getDbPort() ?? 0)
            ->setDbSocket($configData->getDbSocket() ?? '');
    }

    public function refreshFromConfig(ConfigDataInterface $configData): DatabaseConnectionData
    {
        return $this->setDbHost($configData->getDbHost())
            ->setDbName($configData->getDbName())
            ->setDbUser($configData->getDbUser())
            ->setDbPass($configData->getDbPass())
            ->setDbPort($configData->getDbPort())
            ->setDbSocket($configData->getDbSocket());
    }

    public function getDbHost(): ?string
    {
        return $this->dbHost;
    }

    public function setDbHost(string $dbHost): DatabaseConnectionData
    {
        $this->dbHost = $dbHost;

        return $this;
    }

    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): DatabaseConnectionData
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->dbUser;
    }

    public function setDbUser(string $dbUser): DatabaseConnectionData
    {
        $this->dbUser = $dbUser;

        return $this;
    }

    public function getDbPass(): ?string
    {
        return $this->dbPass;
    }

    public function setDbPass(string $dbPass): DatabaseConnectionData
    {
        $this->dbPass = $dbPass;

        return $this;
    }

    public function getDbPort(): ?int
    {
        return $this->dbPort;
    }

    public function setDbPort(int $dbPort): DatabaseConnectionData
    {
        $this->dbPort = $dbPort;

        return $this;
    }

    public function getDbSocket(): ?string
    {
        return $this->dbSocket;
    }

    public function setDbSocket(?string $dbSocket): DatabaseConnectionData
    {
        $this->dbSocket = $dbSocket;

        return $this;
    }

    public static function getFromEnvironment(): DatabaseConnectionData
    {
        return (new self())
            ->setDbHost(getenv('DB_SERVER'))
            ->setDbName(getenv('DB_NAME'))
            ->setDbUser(getenv('DB_USER'))
            ->setDbPass(getenv('DB_PASS'))
            ->setDbPort((int)getenv('DB_PORT'))
            ->setDbSocket(getenv('DB_SOCKET'));
    }
}