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

namespace SP\DataModel;

use SP\Services\User\UserLoginResponse;

/**
 * Class UserLoginData
 *
 * @package SP\DataModel
 */
class UserLoginData
{
    /**
     * @var string
     */
    protected $loginUser;
    /**
     * @var string
     */
    protected $loginPass;
    /**
     * @var UserLoginResponse
     */
    protected $userLoginResponse;

    /**
     * Login del usuario introducido en el formulario
     *
     * @return string
     */
    public function getLoginUser()
    {
        return $this->loginUser;
    }

    /**
     * @param string $login
     */
    public function setLoginUser($login)
    {
        $this->loginUser = $login;
    }

    /**
     * Clave del usuario introducida en el formulario
     *
     * @return string
     */
    public function getLoginPass()
    {
        return $this->loginPass;
    }

    /**
     * @param string $loginPass
     */
    public function setLoginPass($loginPass)
    {
        $this->loginPass = $loginPass;
    }

    /**
     * @return UserLoginResponse
     */
    public function getUserLoginResponse()
    {
        return $this->userLoginResponse;
    }

    /**
     * @param UserLoginResponse $userLoginResponse
     */
    public function setUserLoginResponse(UserLoginResponse $userLoginResponse = null)
    {
        $this->userLoginResponse = $userLoginResponse;
    }
}