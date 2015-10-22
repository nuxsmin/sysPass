<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Mgmt\User;

use SP\Core\Session;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
     * Obtener el Id de usuario a partir del login.
     *
     * @param string $login con el login del usuario
     * @return false|int con el Id del usuario
     */
    public static function getUserIdByLogin($login)
    {
        $query = 'SELECT user_id FROM usrData WHERE user_login = :login LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($login, 'login');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return (int)$queryRes->user_id;
    }


    /**
     * Comprobar si un usuario está deshabilitado.
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsDisabled($userLogin)
    {
        $query = 'SELECT BIN(user_isDisabled) AS user_isDisabled FROM usrData WHERE user_login = :login LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin, 'login');

        $queryRes = DB::getResults($Data);

        $ret = ($queryRes !== false && intval($queryRes->user_isDisabled) === 1);

        return $ret;
    }

    /**
     * Comprobar si un usuario y email existen.
     *
     * @param string $login con el login del usuario
     * @param string $email con el email del usuario
     * @return bool
     */
    public static function checkUserMail($login, $email)
    {
        $query = 'SELECT user_id FROM usrData WHERE user_login = :login AND user_email = :email LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($login, 'login');
        $Data->addParam($email, 'email');

        return (DB::getQuery($Data) === true && DB::$lastNumRows === 1);
    }

    /**
     * Obtener el email de un usuario.
     *
     * @param int $userId con el Id del usuario
     * @return string con el email del usuario
     */
    public static function getUserEmail($userId)
    {
        $query = 'SELECT user_email FROM usrData WHERE user_id = :id AND user_email IS NOT NULL LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId, 'id');

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
        $query = 'UPDATE usrData SET user_lastLogin = NOW(),user_count = user_count + 1 WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId, 'id');

        return DB::getQuery($Data);
    }


    /**
     * Obtener los datos de un usuario.
     *
     * @param int $id con el Id del usuario a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getUserData($id = 0)
    {
        // Array con los nombres de los campos para devolverlos con el formato correcto
        // Es necesario que coincidan con las columnas de la tabla
        $user = array('user_id' => 0,
            'user_name' => '',
            'user_login' => '',
            'user_profileId' => 0,
            'user_groupId' => 0,
            'user_email' => '',
            'user_notes' => '',
            'user_isAdminApp' => 0,
            'user_isAdminAcc' => 0,
            'user_isLdap' => 0,
            'user_isDisabled' => 0,
            'user_isChangePass' => 0,
            'user_count' => 0,
            'user_lastLogin' => '',
            'user_lastUpdate' => '',
            'user_lastUpdateMPass' => 0,
            'action' => 1,
            'checks' => array(
                'user_isLdap' => 0,
                'user_isAdminApp' => 0,
                'user_isAdminAcc' => 0,
                'user_isDisabled' => 0,
                'user_isChangePass' => 0
            )
        );

        if ($id > 0) {
            $users = self::getUsers($id);

            if ($users) {
                foreach ($users[0] as $name => $value) {
                    // Check if field is a checkbox one
                    if (preg_match('/^.*_is[A-Z].*$/', $name)) {
                        $user['checks'][$name] = ((int)$value === 1) ? 'CHECKED' : '';
                    }

                    if ($value === '0000-00-00 00:00:00' || $value === '1970-01-01 01:00:00') {
                        $value = _('N/D');
                    }

                    $user[$name] = $value;
                }
                $user['action'] = 2;
            }
        }

        return $user;
    }

    /**
     * Establecer las variables para la consulta de usuarios.
     *
     * @param int $itemId opcional, con el Id del usuario a consultar
     * @return false|array con la lista de usuarios
     */
    public static function getUsers($itemId = null)
    {
        $data = null;

        $Data = new QueryData();

        if (!is_null($itemId)) {
            $query = 'SELECT user_id,'
                . 'user_name,'
                . 'user_login,'
                . 'user_profileId,'
                . 'user_groupId,'
                . 'user_email,'
                . 'user_notes,'
                . 'BIN(user_isAdminApp) AS user_isAdminApp,'
                . 'BIN(user_isAdminAcc) AS user_isAdminAcc,'
                . 'BIN(user_isLdap) AS user_isLdap,'
                . 'BIN(user_isDisabled) AS user_isDisabled,'
                . 'BIN(user_isChangePass) AS user_isChangePass,'
                . 'user_count,'
                . 'user_lastLogin,'
                . 'user_lastUpdate, '
                . 'FROM_UNIXTIME(user_lastUpdateMPass) as user_lastUpdateMPass '
                . 'FROM usrData '
                . 'LEFT JOIN usrProfiles ON user_profileId = userprofile_id '
                . 'LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id '
                . 'WHERE user_id = :id LIMIT 1';

            $Data->addParam($itemId, 'id');
        } else {
            $query = 'SELECT user_id,'
                . 'user_name,'
                . 'user_login,'
                . 'userprofile_name,'
                . 'usergroup_name,'
                . 'BIN(user_isAdminApp) AS user_isAdminApp,'
                . 'BIN(user_isAdminAcc) AS user_isAdminAcc,'
                . 'BIN(user_isLdap) AS user_isLdap,'
                . 'BIN(user_isDisabled) AS user_isDisabled,'
                . 'BIN(user_isChangePass) AS user_isChangePass '
                . 'FROM usrData '
                . 'LEFT JOIN usrProfiles ON user_profileId = userprofile_id '
                . 'LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id ';

            $query .= (!Session::getUserIsAdminApp()) ? 'WHERE user_isAdminApp = 0 ORDER BY user_name' : 'ORDER BY user_name';
        }

        $Data->setQuery($query);

        DB::setReturnArray();

        return DB::getResults($Data);
    }


    /**
     * Obtener el login de usuario a partir del Id.
     *
     * @param int $id con el id del usuario
     * @return string con el login del usuario
     */
    public static function getUserLoginById($id)
    {
        $query = 'SELECT user_login FROM usrData WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_login;
    }
}