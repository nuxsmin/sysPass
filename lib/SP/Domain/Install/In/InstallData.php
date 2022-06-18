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
 */namespace SP\Domain\Install\In;

/**
 * Class InstallData
 *
 * @package SP\DataModel
 */
final class InstallData
{
    public const BACKEND_MYSQL = 'mysql';

    private ?string $dbAdminUser    = null;
    private ?string $dbAdminPass    = null;
    private string  $dbName         = 'syspass';
    private string  $dbHost         = 'localhost';
    private ?string $dbSocket       = null;
    private int     $dbPort         = 0;
    private ?string $adminLogin     = null;
    private ?string $adminPass      = null;
    private ?string $masterPassword = null;
    private bool    $hostingMode    = false;
    private ?string $dbAuthHost     = null;
    private ?string $dbAuthHostDns  = null;
    private string  $siteLang       = 'en_US';
    private string  $backendType    = self::BACKEND_MYSQL;

    public function getDbName(): string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): void
    {
        $this->dbName = $dbName;
    }

    public function getDbHost(): string
    {
        return $this->dbHost;
    }

    public function setDbHost(string $dbHost): void
    {
        $this->dbHost = $dbHost;
    }

    public function getAdminLogin(): ?string
    {
        return $this->adminLogin;
    }

    public function setAdminLogin(string $adminLogin): void
    {
        $this->adminLogin = $adminLogin;
    }

    public function getAdminPass(): ?string
    {
        return $this->adminPass;
    }

    public function setAdminPass(string $adminPass): void
    {
        $this->adminPass = $adminPass;
    }

    public function getMasterPassword(): ?string
    {
        return $this->masterPassword;
    }

    public function setMasterPassword(string $masterPassword): void
    {
        $this->masterPassword = $masterPassword;
    }

    public function isHostingMode(): bool
    {
        return $this->hostingMode;
    }

    public function setHostingMode(bool $hostingMode): void
    {
        $this->hostingMode = $hostingMode;
    }

    public function getDbAuthHost(): ?string
    {
        return $this->dbAuthHost;
    }

    public function setDbAuthHost(string $dbAuthHost): void
    {
        $this->dbAuthHost = $dbAuthHost;
    }

    public function getDbPort(): int
    {
        return $this->dbPort;
    }

    public function setDbPort(int $dbPort): void
    {
        $this->dbPort = $dbPort;
    }

    public function getDbAdminUser(): ?string
    {
        return $this->dbAdminUser;
    }

    public function setDbAdminUser(string $dbAdminUser): void
    {
        $this->dbAdminUser = $dbAdminUser;
    }

    public function getDbAdminPass(): ?string
    {
        return $this->dbAdminPass;
    }

    public function setDbAdminPass(string $dbAdminPass): void
    {
        $this->dbAdminPass = $dbAdminPass;
    }

    public function getSiteLang(): string
    {
        return $this->siteLang;
    }

    public function setSiteLang(string $siteLang): void
    {
        $this->siteLang = $siteLang;
    }

    public function getDbAuthHostDns(): ?string
    {
        return $this->dbAuthHostDns;
    }

    public function setDbAuthHostDns(string $dbAuthHostDns): void
    {
        $this->dbAuthHostDns = $dbAuthHostDns;
    }

    public function getDbSocket(): ?string
    {
        return $this->dbSocket;
    }

    public function setDbSocket(string $dbSocket): void
    {
        $this->dbSocket = $dbSocket;
    }

    public function getBackendType(): string
    {
        return $this->backendType;
    }

    public function setBackendType(string $backendType): void
    {
        $this->backendType = $backendType;
    }
}