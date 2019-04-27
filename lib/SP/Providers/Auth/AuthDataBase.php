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

namespace SP\Providers\Auth;

/**
 * Class AuthData
 *
 * @package Auth
 */
abstract class AuthDataBase
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var bool
     */
    protected $authenticated;
    /**
     * @var int
     */
    protected $statusCode = 0;
    /**
     * @var string
     */
    protected $server;
    /**
     * @var bool
     */
    protected $authGranted = false;
    /**
     * @var bool
     */
    protected $failed = false;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * @param bool $authenticated
     *
     * @return $this
     */
    public function setAuthenticated($authenticated = null)
    {
        $this->authenticated = $authenticated !== null ? (bool)$authenticated : null;

        return $this;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return (int)$this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = (int)$statusCode;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return bool
     */
    public function isAuthGranted()
    {
        return (bool)$this->authGranted;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @param bool $authGranted
     */
    public function setAuthGranted($authGranted)
    {
        $this->authGranted = (bool)$authGranted;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }
}