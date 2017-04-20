<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Auth\Database;

use SP\Auth\AuthInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserLoginData;
use SP\Log\Log;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserMigrate;

/**
 * Class Database
 *
 * Autentificación basada en base de datos
 *
 * @package SP\Auth\Database
 */
class Database implements AuthInterface
{
    /**
     * @var UserLoginData $UserData
     */
    protected $UserData;

    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $UserData Datos del usuario
     * @return DatabaseAuthData
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    public function authenticate(UserLoginData $UserData)
    {
        $this->UserData = $UserData;

        $AuthData = new DatabaseAuthData();
        $AuthData->setAuthenticated($this->authUser());

        return $AuthData;
    }

    /**
     * Autentificación de usuarios con BD.
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    protected function authUser()
    {
        try {
            User::getItem($this->UserData)->getByLogin($this->UserData->getLogin());

            if ($this->UserData->isUserIsMigrate() && !UserMigrate::migrateUserPass($this->UserData)) {
                return false;
            }

            return Hash::checkHashKey($this->UserData->getLoginPass(), $this->UserData->getUserPass());
        } catch (SPException $e) {
            $Log = new Log();
            $LogMessage = $Log->getLogMessage();
            $LogMessage->setAction(__FUNCTION__);
            $LogMessage->addDescription($e->getMessage());
            $LogMessage->addDetails(__('Login', false), $this->UserData->getLogin());
            $Log->writeLog();

            return false;
        }
    }
}