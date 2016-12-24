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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class UserPass para la gestión de las claves de un usuario
 *
 * @package SP
 */
class UserPass
{
    /**
     * @var int El último id de una consulta de actualización
     */
    public static $queryLastId = 0;

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @param User $User
     * @return bool
     */
    public static function checkUserMPass(User $User)
    {
        $userMPass = $User->getUserMPass(true);

        if ($userMPass === false) {
            return false;
        }

        $configHashMPass = ConfigDB::getValue('masterPwd');

        if ($configHashMPass === false || is_null($configHashMPass)) {
            return false;
        }

        // Comprobamos el hash de la clave del usuario con la guardada
        return Crypt::checkHashPass($userMPass, $configHashMPass, true);
    }

    /**
     * Comprobar si el usuario tiene actualizada la clave maestra actual.
     *
     * @param string $login opcional con el login del usuario
     * @return bool
     */
    public static function checkUserUpdateMPass($login = null)
    {
        $userId = (!is_null($login)) ? UserUtil::getUserIdByLogin($login) : Session::getUserId();

        if ($userId === 0) {
            return false;
        }

        $configMPassTime = ConfigDB::getValue('lastupdatempass');

        if ($configMPassTime === false) {
            return false;
        }

        $query = 'SELECT user_lastUpdateMPass FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $userId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        $ret = ($queryRes !== false && $queryRes->user_lastUpdateMPass > $configMPassTime);

        return $ret;

    }

    /**
     * Modificar la clave de un usuario.
     *
     * @param $userId
     * @param $userPass
     * @return bool
     */
    public static function updateUserPass($userId, $userPass)
    {
        $passdata = self::makeUserPassHash($userPass);
        $userLogin = UserUtil::getUserLoginById($userId);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_isChangePass = 0,'
            . 'user_lastUpdate = NOW() '
            . 'WHERE user_id = :id LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];
        $data['id'] = $userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        Log::writeNewLogAndEmail(_('Modificar Clave Usuario'), sprintf('%s : %s', Html::strongText(_('Login')), $userLogin));

        return true;
    }

    /**
     * Crear la clave de un usuario.
     *
     * @param string $userPass con la clave del usuario
     * @return array con la clave y salt del usuario
     */
    public static function makeUserPassHash($userPass)
    {
        $salt = Crypt::makeHashSalt();
        $userPass = crypt($userPass, $salt);

        return array('salt' => $salt, 'pass' => $userPass);
    }

    /**
     * Obtener el IV del usuario a partir del Id.
     *
     * @param int $id El id del usuario
     * @return string El hash
     */
    public static function getUserIVById($id)
    {
        $query = 'SELECT user_mIV FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_mIV;
    }
}