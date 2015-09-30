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
 * Class UserUtil
 *
 * @package SP
 */
class UserUtil
{
    /**
     * Tiempo máximo para recuperar la clave
     */
    const MAX_PASS_RECOVER_TIME = 3600;
    /**
     * Número de intentos máximos para recuperar la clave
     */
    const MAX_PASS_RECOVER_LIMIT = 3;
    const USER_LOGIN_EXIST = 1;
    const USER_MAIL_EXIST = 2;
    /**
     * @var int El último id de una consulta de actualización
     */
    public static $queryLastId = 0;

    /**
     * Comprobar si un usuario está migrado desde phpPMS.
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsMigrate($userLogin)
    {
        $query = 'SELECT BIN(user_isMigrate) AS user_isMigrate FROM usrData WHERE user_login = :login LIMIT 1';

        $data['login'] = $userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        return ($queryRes !== false && $queryRes->user_isMigrate == 1);
    }

    /**
     * Actualizar la clave de un usuario desde phpPMS.
     *
     * @param string $userLogin con el login del usuario
     * @param string $userPass  con la clave del usuario
     * @return bool
     *
     * Esta función actualiza la clave de un usuario que ha sido migrado desde phpPMS
     */
    public static function migrateUser($userLogin, $userPass)
    {
        $passdata = UserUtil::makeUserPassHash($userPass);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_lastUpdate = NOW(),'
            . 'user_isMigrate = 0 '
            . 'WHERE user_login = :login '
            . 'AND user_isMigrate = 1 '
            . 'AND (user_pass = SHA1(CONCAT(user_hashSalt,:passOld)) '
            . 'OR user_pass = MD5(:passOldMd5)) LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];
        $data['login'] = $userLogin;
        $data['passOld'] = $userPass;
        $data['passOldMd5'] = $userPass;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $log = new Log(__FUNCTION__);
        $log->addDescription(_('Usuario actualizado'));
        $log->addDescription('Login: ' . $userLogin);
        $log->writeLog();

        Email::sendEmail($log);

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
     * Comprobar si el usuario tiene actualizada la clave maestra actual.
     *
     * @param string $login opcional con el login del usuario
     * @return bool
     */
    public static function checkUserUpdateMPass($login = null)
    {
        $userId = (!is_null($login)) ? self::getUserIdByLogin($login) : Session::getUserId();

        if ($userId === 0) {
            return false;
        }

        $configMPassTime = Config::getConfigDbValue('lastupdatempass');

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
     * Obtener el Id de usuario a partir del login.
     *
     * @param string $login con el login del usuario
     * @return false|int con el Id del usuario
     */
    public static function getUserIdByLogin($login)
    {
        $query = 'SELECT user_id FROM usrData WHERE user_login = :login LIMIT 1';

        $data = array('login' => $login);

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return (int)$queryRes->user_id;
    }

    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   con los usuarios de la cuenta
     * @return bool
     */
    public static function updateUsersForAccount($accountId, $usersId)
    {
        if (self::deleteUsersForAccount($accountId, $usersId)) {
            return self::addUsersForAccount($accountId, $usersId);
        }

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   opcional con los grupos de la cuenta
     * @return bool
     */
    public static function deleteUsersForAccount($accountId, $usersId = null)
    {
        $queryExcluded = '';

        // Excluimos los usuarios actuales
        if (is_array($usersId)) {
            array_map('intval', $usersId);
            $queryExcluded = 'AND accuser_userId NOT IN (' . implode(',', $usersId) . ')';
        }

        $query = 'DELETE FROM accUsers WHERE accuser_accountId = :id ' . $queryExcluded;

        $data['id'] = $accountId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Crear asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $usersId   con los grupos de la cuenta
     * @return bool
     */
    public static function addUsersForAccount($accountId, $usersId)
    {
        if (!is_array($usersId)) {
            return true;
        }

        $values = '';

        // Obtenemos los grupos actuales
        $usersExcluded = self::getUsersForAccount($accountId);

        foreach ($usersId as $userId) {
            // Excluimos los usuarios actuales
            if (isset($usersExcluded) && is_array($usersExcluded) && in_array($userId, $usersExcluded)) {
                continue;
            }

            $values[] = '(' . (int)$accountId . ',' . (int)$userId . ')';
        }

        if (!is_array($values)) {
            return true;
        }

        $query = 'INSERT INTO accUsers (accuser_accountId, accuser_userId) VALUES ' . implode(',', $values);

        return DB::getQuery($query, __FUNCTION__);
    }

    /**
     * Obtiene el listado de usuarios de una cuenta.
     *
     * @param int $accountId con el id de la cuenta
     * @return array con los id de usuarios de la cuenta
     */
    public static function getUsersForAccount($accountId)
    {
        $query = 'SELECT accuser_userId FROM accUsers WHERE accuser_accountId = :id';

        $data['id'] = $accountId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        foreach ($queryRes as $user) {
            $users[] = (int)$user->accuser_userId;
        }

        return $users;
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

        $data['login'] = $userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        $ret = ($queryRes !== false && intval($queryRes->user_isDisabled) === 1);

        return $ret;
    }

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param string $hash con el hash de recuperación
     * @return int con el Id del usuario
     */
    public static function checkHashPassRecover($hash)
    {
        $query = 'SELECT userpassr_userId FROM usrPassRecover '
            . 'WHERE userpassr_hash = :hash '
            . 'AND userpassr_used = 0 '
            . 'AND userpassr_date >= :date '
            . 'ORDER BY userpassr_date DESC LIMIT 1';

        $data['hash'] = $hash;
        $data['date'] = time() - self::MAX_PASS_RECOVER_TIME;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->userpassr_userId;
    }

    /**
     * Marcar como usado el hash de recuperación de clave.
     *
     * @param string $hash con el hash de recuperación
     * @return bool
     */
    public static function updateHashPassRecover($hash)
    {
        $query = 'UPDATE usrPassRecover SET userpassr_used = 1 WHERE userpassr_hash = :hash';

        $data['hash'] = $hash;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Comprobar el límite de recuperaciones de clave.
     *
     * @param string $login con el login del usuario
     * @return bool
     */
    public static function checkPassRecoverLimit($login)
    {
        $query = 'SELECT userpassr_userId ' .
            'FROM usrPassRecover ' .
            'WHERE userpassr_userId = :id ' .
            'AND userpassr_used = 0 ' .
            'AND userpassr_date >= :date';

        $data['login'] = self::getUserIdByLogin($login);
        $data['date'] = time() - self::MAX_PASS_RECOVER_TIME;

        $db = new DB();
        $db->setParamData($data);

        return (DB::getQuery($query, __FUNCTION__, $data) === false || DB::$lastNumRows >= self::MAX_PASS_RECOVER_LIMIT);
    }

    /**
     * Obtiene el listado con el nombre de los usuarios de una cuenta.
     *
     * @param int $accountId con el id de la cuenta
     * @return false|array con los nombres de los usuarios ordenados
     */
    public static function getUsersNameForAccount($accountId)
    {
        $query = 'SELECT user_id,'
            . 'user_login '
            . 'FROM accUsers '
            . 'JOIN usrData ON user_Id = accuser_userId '
            . 'WHERE accuser_accountId = :id';

        $data['id'] = $accountId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $users) {
            $usersName[$users->user_id] = $users->user_login;
        }

        asort($usersName, SORT_STRING);

        return $usersName;
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

        $data['login'] = $login;
        $data['email'] = $email;

        return (DB::getQuery($query, __FUNCTION__, $data) === true && DB::$lastNumRows === 1);
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

        $data['id'] = $userId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_email;
    }

    /**
     * Insertar un registro de recuperación de clave.
     *
     * @param string $login con el login del usuario
     * @param string $hash  con el hash para el cambio
     * @return bool
     */
    public static function addPassRecover($login, $hash)
    {
        $query = 'INSERT INTO usrPassRecover SET '
            . 'userpassr_userId = :userId,'
            . 'userpassr_hash = :hash,'
            . 'userpassr_date = UNIX_TIMESTAMP(),'
            . 'userpassr_used = 0';

        $data['userId'] = self::getUserIdByLogin($login);
        $data['hash'] = $hash;

        return DB::getQuery($query, __FUNCTION__, $data);
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


    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $userId int El id del usuario
     * @return bool
     */
    public static function setUserLastLogin($userId)
    {
        $query = 'UPDATE usrData SET user_lastLogin = NOW(),user_count = user_count + 1 WHERE user_id = :id LIMIT 1';

        $data['id'] = $userId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

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

        $configHashMPass = Config::getConfigDbValue('masterPwd');

        if ($configHashMPass === false) {
            return false;
        }

        // Comprobamos el hash de la clave del usuario con la guardada
        return Crypt::checkHashPass($userMPass, $configHashMPass, true);
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

            $data['id'] = $itemId;
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

        DB::setReturnArray();

        return DB::getResults($query, __FUNCTION__, $data);
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
        $passdata = UserUtil::makeUserPassHash($userPass);
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
     * Obtener el login de usuario a partir del Id.
     *
     * @param int $id con el id del usuario
     * @return string con el login del usuario
     */
    public static function getUserLoginById($id)
    {
        $query = 'SELECT user_login FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_login;
    }

    /**
     * Migrar el grupo de los usuarios a la nueva tabla
     */
    public static function migrateUsersGroup()
    {
        $query = 'SELECT user_id, user_groupId FROM usrData';

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $user) {
            if (!Groups::addUsersForGroup(array($user->user_groupId), $user->user_id)) {
                Log::writeNewLog(_('Migrar Grupos'), sprintf('%s (%s)'), _('Error al migrar grupo del usuario'), $user->user_id);
            }
        }

        return true;
    }

    /**
     * Establecer el campo isMigrate de cada usuario
     */
    public static function setMigrateUsers()
    {
        $query = 'UPDATE usrData SET user_isMigrate = 1';

        return DB::getQuery($query, __FUNCTION__);
    }
}