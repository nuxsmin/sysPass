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

namespace SP\Mgmt\Users;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\DataModel\UserData;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class UserUtil
 *
 * @package SP
 */
class UserUtil
{
    const USER_LOGIN_EXIST = 1;
    const USER_MAIL_EXIST = 2;

    /**
     * Comprobar si un usuario y email existen.
     *
     * @param UserData $UserData
     * @return bool
     */
    public static function checkUserMail(UserData $UserData)
    {
        $query = /** @lang SQL */
            'SELECT user_id FROM usrData WHERE user_login = ? AND user_email = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($UserData->getUserLogin());
        $Data->addParam($UserData->getUserEmail());

        return (DB::getQuery($Data) === true && $Data->getQueryNumRows() === 1);
    }

    /**
     * Obtener el email de un usuario.
     *
     * @param int $userId con el Id del usuario
     * @return string con el email del usuario
     */
    public static function getUserEmail($userId)
    {
        $query = /** @lang SQL */
            'SELECT user_email FROM usrData WHERE user_id = ? AND user_email IS NOT NULL LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_email;
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $userId int El id del usuario
     * @return bool
     */
    public static function setUserLastLogin($userId)
    {
        $query = /** @lang SQL */
            'UPDATE usrData SET user_lastLogin = NOW(), user_count = user_count + 1 WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId);

        return DB::getQuery($Data);
    }


    /**
     * Obtener el login de usuario a partir del Id.
     *
     * @param int $id con el id del usuario
     * @return string con el login del usuario
     */
    public static function getUserLoginById($id)
    {
        $query = /** @lang SQL */
            'SELECT user_login FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_login;
    }

    /**
     * Obtener el id y login de los usuarios disponibles
     *
     * @return UserData[]
     */
    public static function getUsersLogin()
    {
        $query = /** @lang SQL */
            'SELECT user_id, user_login, user_name FROM usrData ORDER BY user_login';

        $Data = new QueryData();
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

}