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

namespace SP\Mgmt\Users;

defined('APP_ROOT') || die();

use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\DataModel\GroupUsersData;
use SP\DataModel\UserLoginData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Groups\GroupUsers;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class UserMigrate para la migración de usuarios
 *
 * @package SP
 */
class UserMigrate
{
    /**
     * Comprobar si un usuario está migrado desde phpPMS.
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsMigrate($userLogin)
    {
        $query = /** @lang SQL */
            'SELECT BIN(user_isMigrate) AS user_isMigrate FROM usrData WHERE user_login = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin);

        $queryRes = DB::getResults($Data);

        return ($queryRes !== false && $Data->getQueryNumRows() === 1 && $queryRes->user_isMigrate == 1);
    }

    /**
     * Actualizar la clave de un usuario desde phpPMS.
     *
     * @param UserLoginData $UserData
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     *
     * Esta función actualiza la clave de un usuario que ha sido migrado desde phpPMS
     */
    public static function migrateUserPass(UserLoginData $UserData)
    {
        $passOk = ($UserData->getUserPass() === sha1($UserData->getUserHashSalt() . $UserData->getLoginPass())
            || $UserData->getUserPass() === md5($UserData->getLoginPass())
            || hash_equals($UserData->getUserPass(), crypt($UserData->getLoginPass(), $UserData->getUserHashSalt()))
            || Hash::checkHashKey($UserData->getLoginPass(), $UserData->getUserPass()));

        if ($passOk) {
            $query = /** @lang SQL */
                'UPDATE usrData SET
            user_pass = ?,
            user_hashSalt = \'\',
            user_lastUpdate = NOW(),
            user_isMigrate = 0
            WHERE user_login = ? LIMIT 1';

            $Data = new QueryData();
            $Data->setQuery($query);
            $Data->addParam(Hash::hashKey($UserData->getLoginPass()));
            $Data->addParam($UserData->getLogin());
            $Data->setOnErrorMessage(__('Error al migrar cuenta de usuario', false));

            DB::getQuery($Data);

            $Log = new Log();
            $Log->getLogMessage()
                ->setAction(__FUNCTION__)
                ->addDescription(__('Usuario actualizado', false))
                ->addDetails(__('Login', false), $UserData->getLogin());
            $Log->writeLog();

            Email::sendEmail($Log->getLogMessage());

            return true;
        }

        return false;
    }

    /**
     * Migrar el grupo de los usuarios a la nueva tabla
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public static function migrateUsersGroup()
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__FUNCTION__);

        $query = /** @lang SQL */
            'SELECT user_id, user_groupId FROM usrData';

        $Data = new QueryData();
        $Data->setQuery($query);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            $LogMessage->addDescription(__('Error al obtener grupo de usuarios', false));
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, $LogMessage->getDescription());
        }

        foreach ($queryRes as $user) {
            $GroupUsers = new GroupUsersData();
            $GroupUsers->setUsertogroupGroupId($user->user_groupId);
            $GroupUsers->addUser($user->user_id);

            try {
                GroupUsers::getItem($GroupUsers)->update();
            } catch (SPException $e) {
                $LogMessage->addDetails(__('Error al migrar grupo del usuario', false), $user->user_id);
                $Log->setLogLevel(Log::ERROR);
            }
        }

        $Log->writeLog();

        return true;
    }

    /**
     * Establecer el campo isMigrate de cada usuario
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public static function setMigrateUsers()
    {
        $query = 'UPDATE usrData SET user_isMigrate = 1';

        $Data = new QueryData();
        $Data->setQuery($query);

        return DB::getQuery($Data);
    }
}