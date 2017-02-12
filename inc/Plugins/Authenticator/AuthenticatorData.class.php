<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace Plugins\Authenticator;

/**
 * Class AuthenticatorData
 *
 * @package Plugins\Authenticator
 */
class AuthenticatorData
{
    /**
     * Id de usuario
     *
     * @var
     */
    public $userId;
    /**
     * Si está habilitado 2FA
     *
     * @var int
     */
    public $twofaEnabled = 0;
    /**
     * Fecha de activación
     *
     * @var int
     */
    public $date;
    /**
     * Días de caducidad
     *
     * @var int
     */
    public $expireDays;
    /**
     * Vector de inicialización
     *
     * @var string
     */
    public $IV;

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return (int)$this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return bool
     */
    public function isTwofaEnabled()
    {
        return (bool)$this->twofaEnabled;
    }

    /**
     * @param int $twofaEnabled
     */
    public function setTwofaEnabled($twofaEnabled)
    {
        $this->twofaEnabled = (int)$twofaEnabled;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getExpireDays()
    {
        return $this->expireDays;
    }

    /**
     * @param int $expireDays
     */
    public function setExpireDays($expireDays)
    {
        $this->expireDays = $expireDays;
    }

    /**
     * @return string
     */
    public function getIV()
    {
        return $this->IV;
    }

    /**
     * @param string $IV
     */
    public function setIV($IV)
    {
        $this->IV = $IV;
    }
}