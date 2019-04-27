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

namespace SP\Services\Install;

/**
 * Class InstallData
 *
 * @package SP\DataModel
 */
final class InstallData
{
    /**
     * @var string Usuario de la BD
     */
    private $dbUser;
    /**
     * @var string
     */
    private $dbAdminUser = '';
    /**
     * @var string Clave de la BD
     */
    private $dbPass;
    /**
     * @var string
     */
    private $dbAdminPass = '';
    /**
     * @var string Nombre de la BD
     */
    private $dbName = 'syspass';
    /**
     * @var string Host de la BD
     */
    private $dbHost = 'localhost';
    /**
     * @var string
     */
    private $dbSocket;
    /**
     * @var int
     */
    private $dbPort = 0;
    /**
     * @var string Usuario 'admin' de sysPass
     */
    private $adminLogin = '';
    /**
     * @var string Clave del usuario 'admin' de sysPass
     */
    private $adminPass = '';
    /**
     * @var string Clave maestra de sysPass
     */
    private $masterPassword = '';
    /**
     * @var bool Activar/desactivar Modo hosting
     */
    private $hostingMode = false;
    /**
     * @var string
     */
    private $dbAuthHost = '';
    /**
     * @var string
     */
    private $dbAuthHostDns = '';
    /**
     * @var string
     */
    private $siteLang = 'en_US';

    /**
     * @return string
     */
    public function getDbUser()
    {
        return $this->dbUser;
    }

    /**
     * @param string $dbUser
     */
    public function setDbUser($dbUser)
    {
        $this->dbUser = $dbUser;
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
     */
    public function setDbPass($dbPass)
    {
        $this->dbPass = $dbPass;
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
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
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
     */
    public function setDbHost($dbHost)
    {
        $this->dbHost = $dbHost;
    }

    /**
     * @return string
     */
    public function getAdminLogin()
    {
        return $this->adminLogin;
    }

    /**
     * @param string $adminLogin
     */
    public function setAdminLogin($adminLogin)
    {
        $this->adminLogin = $adminLogin;
    }

    /**
     * @return string
     */
    public function getAdminPass()
    {
        return $this->adminPass;
    }

    /**
     * @param string $adminPass
     */
    public function setAdminPass($adminPass)
    {
        $this->adminPass = $adminPass;
    }

    /**
     * @return string
     */
    public function getMasterPassword()
    {
        return $this->masterPassword;
    }

    /**
     * @param string $masterPassword
     */
    public function setMasterPassword($masterPassword)
    {
        $this->masterPassword = $masterPassword;
    }

    /**
     * @return boolean
     */
    public function isHostingMode()
    {
        return $this->hostingMode;
    }

    /**
     * @param boolean $hostingMode
     */
    public function setHostingMode($hostingMode)
    {
        $this->hostingMode = $hostingMode;
    }

    /**
     * @return string
     */
    public function getDbAuthHost()
    {
        return $this->dbAuthHost;
    }

    /**
     * @param string $dbAuthHost
     */
    public function setDbAuthHost($dbAuthHost)
    {
        $this->dbAuthHost = $dbAuthHost;
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
     */
    public function setDbPort($dbPort)
    {
        $this->dbPort = $dbPort;
    }

    /**
     * @return string
     */
    public function getDbAdminUser()
    {
        return $this->dbAdminUser;
    }

    /**
     * @param string $dbAdminUser
     */
    public function setDbAdminUser($dbAdminUser)
    {
        $this->dbAdminUser = $dbAdminUser;
    }

    /**
     * @return string
     */
    public function getDbAdminPass()
    {
        return $this->dbAdminPass;
    }

    /**
     * @param string $dbAdminPass
     */
    public function setDbAdminPass($dbAdminPass)
    {
        $this->dbAdminPass = $dbAdminPass;
    }

    /**
     * @return string
     */
    public function getSiteLang()
    {
        return $this->siteLang;
    }

    /**
     * @param string $siteLang
     */
    public function setSiteLang($siteLang)
    {
        $this->siteLang = $siteLang;
    }

    /**
     * @return string
     */
    public function getDbAuthHostDns()
    {
        return $this->dbAuthHostDns;
    }

    /**
     * @param string $dbAuthHostDns
     */
    public function setDbAuthHostDns($dbAuthHostDns)
    {
        $this->dbAuthHostDns = $dbAuthHostDns;
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
     */
    public function setDbSocket($dbSocket)
    {
        $this->dbSocket = $dbSocket;
    }
}