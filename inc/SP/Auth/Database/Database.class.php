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

namespace SP\Auth\Database;

use SP\Auth\AuthInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\DataModel\UserPassData;
use SP\Log\Log;
use SP\Mgmt\Users\UserMigrate;
use SP\Storage\DB;
use SP\Storage\QueryData;

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
     * @var UserData $UserData
     */
    protected $UserData;

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
        if (UserMigrate::checkUserIsMigrate($this->UserData->getUserLogin())) {
            try {
                UserMigrate::migrateUser($this->UserData->getUserLogin(), $this->UserData->getUserPass());
            } catch (SPException $e) {
                $Log = new Log(__FUNCTION__);
                $Log->addDescription($e->getMessage());
                $Log->addDetails(_('Login'), $this->UserData->getUserLogin());
                $Log->writeLog();

                return false;
            }
        }

        $query = /** @lang SQL */
            'SELECT user_pass, user_hashSalt
            FROM usrData
            WHERE user_login = ? 
            AND user_isMigrate = 0 LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\UserPassData');
        $Data->setQuery($query);
        $Data->addParam($this->UserData->getUserLogin());

        /** @var UserPassData $queryRes */
        $queryRes = DB::getResults($Data);

        return ($queryRes !== false
            && $Data->getQueryNumRows() === 1
            && $queryRes->getUserPass() === crypt($this->UserData->getUserPass(), $queryRes->getUserHashSalt()));
    }

    /**
     * Autentificar al usuario
     *
     * @param UserData $UserData Datos del usuario
     * @return DatabaseAuthData
     */
    public function authenticate(UserData $UserData)
    {
        $this->UserData = $UserData;

        $AuthData = new DatabaseAuthData();
        $AuthData->setAuthenticated($this->authUser());

        return $AuthData;
    }
}