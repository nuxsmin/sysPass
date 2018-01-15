<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Providers\Auth\Database;

use SP\Providers\Auth\AuthInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\UserData;
use SP\DataModel\UserLoginData;
use SP\Log\Log;
use SP\Mgmt\Users\UserMigrate;
use SP\Repositories\User\UserRepository;
use SP\Services\User\UserPassService;
use SP\Services\User\UserService;

/**
 * Class Database
 *
 * Autentificación basada en base de datos
 *
 * @package SP\Providers\Auth\Database
 */
class Database implements AuthInterface
{
    /**
     * @var UserLoginData $userLoginData
     */
    protected $userLoginData;

    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $userLoginData Datos del usuario
     * @return DatabaseAuthData
     */
    public function authenticate(UserLoginData $userLoginData)
    {
        $this->userLoginData = $userLoginData;

        $authData = new DatabaseAuthData();
        $authData->setAuthGranted($this->isAuthGranted());
        $authData->setAuthenticated($this->authUser());

        return $authData;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return boolean
     */
    public function isAuthGranted()
    {
        return true;
    }

    /**
     * Autentificación de usuarios con BD.
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @return bool
     */
    protected function authUser()
    {
        try {
            $userLoginResponse = (new UserService())->getByLogin($this->userLoginData->getLoginUser());

            $this->userLoginData->setUserLoginResponse($userLoginResponse);

            if ($userLoginResponse->getIsMigrate() && $this->checkMigrateUser()) {
                return (new UserPassService())->migrateUserPassById($userLoginResponse->getId(), $this->userLoginData->getLoginPass());

            }

            return Hash::checkHashKey($this->userLoginData->getLoginPass(), $userLoginResponse->getPass());
        } catch (SPException $e) {
//            $Log = new Log();
//            $LogMessage = $Log->getLogMessage();
//            $LogMessage->setAction(__FUNCTION__);
//            $LogMessage->addDescription($e->getMessage());
//            $LogMessage->addDetails(__u('Login'), $this->userLoginData->getLoginUser());
//            $Log->writeLog();

            return false;
        }
    }

    /**
     * @return bool
     * @throws SPException
     */
    protected function checkMigrateUser()
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        return ($userLoginResponse->getPass() === sha1($userLoginResponse->getHashSalt() . $this->userLoginData->getLoginPass())
            || $userLoginResponse->getPass() === md5($this->userLoginData->getLoginPass())
            || hash_equals($userLoginResponse->getPass(), crypt($this->userLoginData->getLoginPass(), $userLoginResponse->getHashSalt()))
            || Hash::checkHashKey($this->userLoginData->getLoginPass(), $userLoginResponse->getPass()));
    }
}