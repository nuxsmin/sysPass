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
 * Esta clase es la encargada de realizar las operaciones osbre los usuarios de sysPass
 */
class Users
{
    const USER_LOGIN_EXIST = 1;
    const USER_MAIL_EXIST = 2;
    const MAX_PASS_RECOVER_TIME = 3600;
    const MAX_PASS_RECOVER_LIMIT = 3;

    static $queryRes;
    static $querySelect;
    static $queryFrom;
    static $queryWhere;
    static $queryCount;

    var $userId;
    var $userName;
    var $userGroupId;
    var $userGroupName;
    var $userLogin;
    var $userPass;
    var $userEmail;
    var $userNotes;
    var $userProfileId;
    var $userIsAdminApp;
    var $userIsAdminAcc;
    var $userIsDisabled;
    var $userIsLdap;
    var $userChangePass;
    var $queryLastId;

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
        $passdata = Users::makeUserPass($userPass);

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
    private static function makeUserPass($userPass)
    {
        $salt = Crypt::makeHashSalt();
        $userPass = sha1($salt . $userPass);

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

        if ($userId === 0){
            return false;
        }

        $configMPassTime = Config::getConfigDbValue('lastupdatempass');

        if ($configMPassTime === false) {
            return false;
        }

        $query = 'SELECT user_lastUpdateMPass FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $userId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        return ($queryRes !== false && $queryRes->user_lastUpdateMPass > $configMPassTime);

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
            $users[] = $user->accuser_userId;
        }

        return $users;
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

//        $userId = self::getUserIdByLogin($login);
//        return ($userId && self::getUserEmail($userId) == $email);
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

        return ($queryRes !== false && intval($queryRes->user_isDisabled) === 1);
    }

    /**
     * Comprobar si un usuario autentifica mediante LDAP
     * .
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsLDAP($userLogin)
    {
        $query = 'SELECT BIN(user_isLdap) AS user_isLdap FROM usrData WHERE user_login = :login LIMIT 1';

        $data['login'] = $userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        return ($queryRes !== false && intval($queryRes->user_isLdap) === 1);
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
        //return ($db->getFullRowCount($query) >= self::MAX_PASS_RECOVER_LIMIT);
    }

    /**
     * Obtener los datos de un usuario desde la BBDD.
     * Esta función obtiene los datos de un usuario y los guarda en las variables de la clase.
     *
     * @return bool
     */
    public function getUserInfo()
    {
        $query = 'SELECT user_id,'
            . 'user_name,'
            . 'user_groupId,'
            . 'user_login,'
            . 'user_email,'
            . 'user_notes,'
            . 'user_count,'
            . 'user_profileId,'
            . 'usergroup_name,'
            . 'BIN(user_isAdminApp) AS user_isAdminApp,'
            . 'BIN(user_isAdminAcc) AS user_isAdminAcc,'
            . 'BIN(user_isLdap) AS user_isLdap,'
            . 'BIN(user_isDisabled) AS user_isDisabled,'
            . 'BIN(user_isChangePass) AS user_isChangePass '
            . 'FROM usrData '
            . 'LEFT JOIN usrGroups ON user_groupId = usergroup_id '
            . 'LEFT JOIN usrProfiles ON user_profileId = userprofile_id '
            . 'WHERE user_login = :login LIMIT 1';

        $data['login'] = $this->userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $this->userId = intval($queryRes->user_id);
        $this->userName = $queryRes->user_name;
        $this->userGroupId = intval($queryRes->user_groupId);
        $this->userGroupName = $queryRes->usergroup_name;
        $this->userEmail = $queryRes->user_email;
        $this->userProfileId = intval($queryRes->user_profileId);
        $this->userIsAdminApp = intval($queryRes->user_isAdminApp);
        $this->userIsAdminAcc = intval($queryRes->user_isAdminAcc);
        $this->userIsLdap = intval($queryRes->user_isLdap);
        $this->userChangePass = intval($queryRes->user_isChangePass);

        return true;
    }

    /**
     * Comprobar si un usuario/email existen en la BBDD.
     *
     * @return false|int Devuelve bool si error y int si existe el usuario/email
     */
    public function checkUserExist()
    {
        $userLogin = strtoupper($this->userLogin);
        $userEmail = strtoupper($this->userEmail);

        $query = 'SELECT user_login, user_email '
            . 'FROM usrData '
            . 'WHERE (UPPER(user_login) = :login '
            . 'OR UPPER(user_email) = :email) '
            . 'AND user_id != :id';

        $data['login'] = $userLogin;
        $data['email'] = $userEmail;
        $data['id'] = $this->userId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $userData) {
            $resULogin = strtoupper($userData->user_login);
            $resUEmail = strtoupper($userData->user_email);

            if ($resULogin == $userLogin) {
                return Users::USER_LOGIN_EXIST;
            } elseif ($resUEmail == $userEmail) {
                return Users::USER_MAIL_EXIST;
            }
        }
    }

    /**
     * Comprobar si los datos del usuario de LDAP están en la BBDD.
     *
     * @return bool
     */
    public function checkLDAPUserInDB()
    {
        $query = 'SELECT user_login FROM usrData WHERE user_login = :login LIMIT 1';

        $data['login'] = $this->userLogin;

        return (DB::getQuery($query, __FUNCTION__, $data) === true && DB::$lastNumRows === 1);
//        return ($queryRes === true && $db->getFullRowCount($query) === 1);
    }

    /**
     * Crear un nuevo usuario en la BBDD con los datos de LDAP.
     * Esta función crea los usuarios de LDAP en la BBDD para almacenar infomación del mismo
     * y utilizarlo en caso de fallo de LDAP
     *
     * @return bool
     */
    public function newUserLDAP()
    {
        $passdata = Users::makeUserPass($this->userPass);

        $query = 'INSERT INTO usrData SET '
            . 'user_name = :name,'
            . 'user_groupId = :groupId,'
            . 'user_login = :login,'
            . 'user_pass = :pass,'
            . 'user_hashSalt = :hashSalt,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_profileId = :profileId,'
            . 'user_isLdap = 1,'
            . 'user_isDisabled = 0';

        $data['name'] = $this->userName;
        $data['login'] = $this->userLogin;
        $data['pass'] = $passdata['pass'];
        $data['hashSalt'] = $passdata['hash'];
        $data['email'] = $this->userEmail;
        $data['notes'] = 'LDAP';
        $data['groupId'] = Config::getValue('ldap_defaultgroup', 0);
        $data['profileId'] = Config::getValue('ldap_defaultprofile', 0);

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $log = new Log(_('Activación Cuenta'));
        $log->addDescription(_('Su cuenta está pendiente de activación.'));
        $log->addDescription(_('En breve recibirá un email de confirmación.'));
        $log->writeLog();

        Email::sendEmail($log, $this->userEmail, false);

        return true;
    }

    /**
     * Crear un usuario.
     *
     * @return bool
     */
    public function addUser()
    {
        $passdata = Users::makeUserPass($this->userPass);

        $query = 'INSERT INTO usrData SET '
            . 'user_name = :name,'
            . 'user_login = :login,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_groupId = :groupId,'
            . 'user_profileId = :profileId,'
            . 'user_mPass = \'\','
            . 'user_mIV = \'\','
            . 'user_isAdminApp = :isAdminApp,'
            . 'user_isAdminAcc = :isAdminAcc,'
            . 'user_isDisabled = :isDisabled,'
            . 'user_isChangePass = :isChangePass,'
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_isLdap = 0';

        $data['name'] = $this->userName;
        $data['login'] = $this->userLogin;
        $data['email'] = $this->userEmail;
        $data['notes'] = $this->userNotes;
        $data['groupId'] = $this->userGroupId;
        $data['profileId'] = $this->userProfileId;
        $data['isAdminApp'] = $this->userIsAdminApp;
        $data['isAdminAcc'] = $this->userIsAdminAcc;
        $data['isDisabled'] = $this->userIsDisabled;
        $data['isChangePass'] = $this->userChangePass;
        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->userId = DB::getLastId();

        $log = new Log(_('Nuevo Usuario'));
        $log->addDescription(Html::strongText(_('Usuario') . ': ') . $this->userName . ' (' . $this->userLogin . ')');

        if ($this->userChangePass) {
            if (!Auth::mailPassRecover(DB::escape($this->userLogin), DB::escape($this->userEmail))) {
                $log->addDescription(Html::strongText(_('No se pudo realizar la petición de cambio de clave.')));
            }
        }

        $log->writeLog();

        Email::sendEmail($log);

        return true;
    }

    /**
     * Modificar un usuario.
     *
     * @return bool
     */
    public function updateUser()
    {
        $query = 'UPDATE usrData SET '
            . 'user_name = :name,'
            . 'user_login = :login,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_groupId = :groupId,'
            . 'user_profileId = :profileId,'
            . 'user_isAdminApp = :isAdminApp,'
            . 'user_isAdminAcc = :isAdminAcc,'
            . 'user_isDisabled = :isDisabled,'
            . 'user_isChangePass = :isChangePass,'
            . 'user_lastUpdate = NOW() '
            . 'WHERE user_id = :id LIMIT 1';

        $data['name'] = $this->userName;
        $data['login'] = $this->userLogin;
        $data['email'] = $this->userEmail;
        $data['notes'] = $this->userNotes;
        $data['groupId'] = $this->userGroupId;
        $data['profileId'] = $this->userProfileId;
        $data['isAdminApp'] = $this->userIsAdminApp;
        $data['isAdminAcc'] = $this->userIsAdminAcc;
        $data['isDisabled'] = $this->userIsDisabled;
        $data['isChangePass'] = $this->userChangePass;
        $data['id'] = $this->userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        $log = new Log(_('Modificar Usuario'));
        $log->addDescription(Html::strongText(_('Usuario') . ': ') . $this->userName . ' (' . $this->userLogin . ')');

        if ($this->userChangePass) {
            if (!Auth::mailPassRecover(DB::escape($this->userLogin), DB::escape($this->userEmail))) {
                $log->addDescription(Html::strongText(_('No se pudo realizar la petición de cambio de clave.')));
            }
        }

        $log->writeLog();

        Email::sendEmail($log);

        return true;
    }

    /**
     * Modificar la clave de un usuario.
     *
     * @return bool
     */
    public function updateUserPass()
    {
        $passdata = Users::makeUserPass($this->userPass);
        $userLogin = $this->getUserLoginById($this->userId);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_isChangePass = 0,'
            . 'user_lastUpdate = NOW() '
            . 'WHERE user_id = :id LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];
        $data['id'] = $this->userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        Log::writeNewLogAndEmail(_('Modificar Clave Usuario'), Html::strongText(_('Login') . ': ') . $userLogin);

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
     * Eliminar un usuario.
     *
     * @return bool
     */
    public function deleteUser()
    {
        $userLogin = $this->getUserLoginById($this->userId);

        $query = 'DELETE FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $this->userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        Log::writeNewLogAndEmail(_('Eliminar Usuario'), Html::strongText(_('Login') . ': ') . $userLogin);

        return true;
    }

    /**
     * Actualiza los datos de los usuarios de LDAP en la BBDD.
     *
     * @return bool
     */
    public function updateLDAPUserInDB()
    {
        $passdata = Users::makeUserPass($this->userPass);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :hashSalt,'
            . 'user_name = :name,'
            . 'user_email = :email,'
            . 'user_lastUpdate = NOW(),'
            . 'user_isLdap = 1 '
            . 'WHERE user_id = :id LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['hashSalt'] = $passdata['salt'];
        $data['name'] = $this->userName;
        $data['email'] = $this->userEmail;
        $data['id'] = $this->getUserIdByLogin($this->userLogin);

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Establece las variables de sesión del usuario.
     */
    public function setUserSession()
    {
        Session::setUserLogin($this->userLogin);
        Session::setUserProfileId($this->userProfileId);
        Session::setUserName($this->userName);
        Session::setUserGroupId($this->userGroupId);
        Session::setUserGroupName($this->userGroupName);
        Session::setUserId($this->userId);
        Session::setUserEMail($this->userEmail);
        Session::setUserIsAdminApp($this->userIsAdminApp);
        Session::setUserIsAdminAcc($this->userIsAdminAcc);
        Session::setUserIsLdap($this->userIsLdap);
        Session::setUserProfile(Profile::getProfile($this->userProfileId));

        $this->setUserLastLogin();
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @return bool
     */
    private function setUserLastLogin()
    {
        $query = 'UPDATE usrData SET user_lastLogin = NOW(),user_count = user_count + 1 WHERE user_id = :id LIMIT 1';

        $data['id'] = $this->userId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @return bool
     */
    public function checkUserMPass()
    {
        $userMPass = $this->getUserMPass(true);

        if ($userMPass === false) {
            return false;
        }

        $configMPass = Config::getConfigDbValue('masterPwd');

        if ($configMPass === false) {
            return false;
        }

        // Comprobamos el hash de la clave del usuario con la guardada
        return Crypt::checkHashPass($userMPass, $configMPass);
    }

    /**
     * Desencriptar la clave maestra del usuario para la sesión.
     *
     * @param bool $showPass opcional, para devolver la clave desencriptada
     * @return false|string Devuelve bool se hay error o string si se devuelve la clave
     */
    public function getUserMPass($showPass = false)
    {
        $query = 'SELECT user_mPass, user_mIV FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $this->userId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        if ($queryRes->user_mPass && $queryRes->user_mIV) {
            $clearMasterPass = Crypt::getDecrypt($queryRes->user_mPass, $this->getCypherPass(), $queryRes->user_mIV);

            if (!$clearMasterPass) {
                return false;
            }

            if ($showPass == true) {
                return $clearMasterPass;
            } else {
                $mPassPwd = Util::generate_random_bytes(32);
                Session::setMPassPwd($mPassPwd);

                $sessionMasterPass = Crypt::mkCustomMPassEncrypt($mPassPwd, $clearMasterPass);

                Session::setMPass($sessionMasterPass[0]);
                Session::setMPassIV($sessionMasterPass[1]);
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @return string con la clave de cifrado
     */
    private function getCypherPass()
    {
        $configSalt = Config::getConfigDbValue('passwordsalt');
        $cypherPass = substr(sha1($configSalt . $this->userPass), 0, 32);

        return $cypherPass;
    }

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @param string $masterPwd con la clave maestra
     * @return bool
     */
    public function updateUserMPass($masterPwd)
    {
        $configMPass = Config::getConfigDbValue('masterPwd');

        if (!$configMPass) {
            return false;
        }

        if (Crypt::checkHashPass($masterPwd, $configMPass)) {
            $strUserMPwd = Crypt::mkCustomMPassEncrypt($this->getCypherPass(), $masterPwd);

            if (!$strUserMPwd) {
                return false;
            }
        } else {
            return false;
        }

        $query = 'UPDATE usrData SET '
            . 'user_mPass = :mPass,'
            . 'user_mIV = :mIV,'
            . 'user_lastUpdateMPass = UNIX_TIMESTAMP() '
            . 'WHERE user_id = :id LIMIT 1';

        $data['mPass'] = $strUserMPwd[0];
        $data['mIV'] = $strUserMPwd[1];
        $data['id'] = $this->userId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }
}