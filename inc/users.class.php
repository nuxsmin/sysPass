<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones osbre los usuarios de sysPass
 */
class SP_Users {

    // Variables de usuario
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
    
    // Variables de consulta
    static $queryRes;
    static $querySelect;
    static $queryFrom;
    static $queryWhere;
    static $queryCount;
    var $queryLastId;

    function __construct() {
        $this->debugOn = SP_Config::getValue('debug');
        $this->remoteIP = $_SERVER["REMOTE_ADDR"];
    }

    /**
     * @brief Obtener los datos de un usuario desde la BBDD
     * @return bool
     * 
     * Esta función obtiene los datos de un usuario y los guarda en las variables de la clase.
     */
    public function getUserInfo() {
        $query = "SELECT user_id,"
                . "user_name,"
                . "user_groupId,"
                . "user_login,"
                . "user_email,"
                . "user_notes,"
                . "user_count,"
                . "user_profileId,"
                . "usergroup_name,"
                . "user_isAdminApp,"
                . "user_isAdminAcc,"
                . "user_isLdap,"
                . "user_isDisabled "
                . "FROM usrData "
                . "LEFT JOIN usrGroups ON user_groupId = usergroup_id "
                . "LEFT JOIN usrProfiles ON user_profileId = userprofile_id "
                . "WHERE user_login = '" . DB::escape($this->userLogin) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        $this->userId = (int) $queryRes->user_id;
        $this->userName = $queryRes->user_name;
        $this->userGroupId = (int) $queryRes->user_groupId;
        $this->userGroupName = $queryRes->usergroup_name;
        $this->userEmail = $queryRes->user_email;
        $this->userProfileId = (int) $queryRes->user_profileId;
        $this->userIsAdminApp = (int) $queryRes->user_isAdminApp;
        $this->userIsAdminAcc = (int) $queryRes->user_isAdminAcc;
        $this->userIsLdap = (int) $queryRes->user_isLdap;

        return TRUE;
    }

    /**
     * @brief Obtener el email de un usuario
     * @param int $userId con el Id del usuario
     * @return string con el email del usuario
     */
    public static function getUserEmail($userId) {
        $query = "SELECT user_email "
                . "FROM usrData "
                . "WHERE user_id = " . (int) $userId . " "
                . "AND user_email IS NOT NULL LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->user_email;
    }
    
    /**
     * @brief Establecer las variables para la consulta de usuarios
     * @param int $itemId opcional, con el Id del usuario a consultar
     * @return array con la lista de usuarios
     */
    public static function getUsers($itemId = NULL) {
        if (!is_null($itemId)) {
            $query = "SELECT user_id,"
                    . "user_name,"
                    . "user_login,"
                    . "user_profileId,"
                    . "user_groupId,"
                    . "user_email,"
                    . "user_notes,"
                    . "user_isAdminApp,"
                    . "user_isAdminAcc,"
                    . "user_isLdap,"
                    . "user_isDisabled,"
                    . "user_count,"
                    . "user_lastLogin,"
                    . "user_lastUpdate, "
                    . "FROM_UNIXTIME(user_lastUpdateMPass) as user_lastUpdateMPass "
                    . "FROM usrData "
                    . "LEFT JOIN usrProfiles ON user_profileId = userprofile_id "
                    . "LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id "
                    . "WHERE user_id = " . (int) $itemId . " LIMIT 1";
        } else {
            $query = "SELECT user_id,"
                    . "user_name,"
                    . "user_login,"
                    . "userprofile_name,"
                    . "usergroup_name,"
                    . "user_isAdminApp,"
                    . "user_isAdminAcc,"
                    . "user_isLdap,"
                    . "user_isDisabled "
                    . "FROM usrData "
                    . "LEFT JOIN usrProfiles ON user_profileId = userprofile_id "
                    . "LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id ";
            
            $query .= ( $_SESSION["uisadminapp"] == 0 ) ? "WHERE user_isAdminApp = 0 ORDER BY user_name" : "ORDER BY user_name";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes;
    }

    /**
     * @brief Obtener los datos de un usuario
     * @param int $id con el Id del usuario a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getUserData($id = 0) {
        
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
            'user_count' => 0,
            'user_lastLogin' => '',
            'user_lastUpdate' => '',
            'user_lastUpdateMPass' => 0,
            'action' => 1,
            'checks' => array(
                'user_isLdap' => 0,
                'user_isAdminApp' => 0,
                'user_isAdminAcc' => 0,
                'user_isDisabled' => 0
                )
            );

        if ($id > 0) {
            $users = self::getUsers($id);

            if ($users) {
                foreach ($users[0] as $name => $value) {
                    // Check if field is a checkbox one
                    if (preg_match('/^.*_is[A-Z].*$/', $name)) {
                        $user['checks'][$name] = ( (int) $value === 1 ) ? 'CHECKED' : '';
                    }
                        
                    if ( $value === '0000-00-00 00:00:00' || $value === '1970-01-01 01:00:00' ){
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
     * @brief Comprobar si un usuario/email existen en la BBDD
     * @return bool|int Devuelve bool si error y int si existe el usuario/email
     */
    public function checkUserExist() {
        $userLogin = strtoupper($this->userLogin);
        $userEmail = strtoupper($this->userEmail);

        $query = "SELECT user_login, user_email "
                . "FROM usrData "
                . "WHERE (UPPER(user_login) = '" . DB::escape($userLogin) . "' "
                . "OR UPPER(user_email) = '" . DB::escape($userEmail) . "') "
                . "AND user_id != " . (int) $this->userId;
        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        foreach ($queryRes as $userData) {
            $resULogin = strtoupper($userData->user_login);
            $resUEmail = strtoupper($userData->user_email);

            if ($resULogin == $userLogin) {
                return 1;
            } elseif ($resUEmail == $userEmail) {
                return 2;
            }
        }
    }

    /**
     * @brief Comprobar si los datos del usuario de LDAP están en la BBDD
     * @return bool
     */
    public function checkLDAPUserInDB() {
        $query = "SELECT user_login "
                . "FROM usrData "
                . "WHERE user_login = '" . DB::escape($this->userLogin) . "' LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        if (count(DB::$last_result) == 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si un usuario está migrado desde phpPMS
     * @return bool
     */
    public static function checkUserIsMigrate($userLogin) {
        $query = "SELECT user_isMigrate "
                . "FROM usrData "
                . "WHERE user_login = '" . DB::escape($userLogin) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        if ($queryRes->user_isMigrate == 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Actualizar la clave de un usuario desde phpPMS
     * @return bool
     * 
     * Esta función actualiza la clave de un usuario que ha sido migrado desde phpPMS
     */
    public static function migrateUser($userLogin, $userPass) {
        $passdata = SP_Users::makeUserPass($userPass);

        $query = "UPDATE usrData SET "
                . "user_pass = '" . $passdata['pass'] . "',"
                . "user_hashSalt = '" . $passdata['salt'] . "',"
                . "user_lastUpdate = NOW(),"
                . "user_isMigrate = 0 "
                . "WHERE user_login = '" . DB::escape($userLogin) . "' "
                . "AND user_isMigrate = 1 "
                . "AND (user_pass = SHA1(CONCAT(user_hashSalt,'" . DB::escape($userPass) . "')) "
                . "OR user_pass = MD5('" . DB::escape($userPass) . "')) LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = __FUNCTION__;
        $message['text'][] = _('Usuario actualizado');
        $message['text'][] = 'Login: ' . $userLogin;

        SP_Common::wrLogInfo($message);
        return TRUE;
    }

    /**
     * @brief Crear la clave de un usuario
     * @return array con la clave y salt del usuario
     */
    private static function makeUserPass($userPass) {
        $salt = SP_Crypt::makeHashSalt();
        $userPass = DB::escape(sha1($salt . DB::escape($userPass)));

        return array('salt' => $salt, 'pass' => $userPass);
    }

    /**
     * @brief Crear un nuevo usuario en la BBDD con los datos de LDAP
     * @return bool
     * 
     * Esta función crea los unusario de LDAP en la BBDD para almacenar infomación del mismo
     * y utilizarlo en caso de fallo de LDAP
     */
    public function newUserLDAP() {
        $passdata = SP_Users::makeUserPass($this->userPass);

        $query = "INSERT INTO usrData SET "
                . "user_name = '" . DB::escape($this->userName) . "',"
                . "user_groupId = 0,"
                . "user_login = '" . DB::escape($this->userLogin) . "',"
                . "user_pass = '" . $passdata['pass'] . "',"
                . "user_hashSalt = '" . $passdata['salt'] . "',"
                . "user_email = '" . DB::escape($this->userEmail) . "',"
                . "user_notes = 'LDAP',"
                . "user_profileId = 0,"
                . "user_isLdap = 1,"
                . "user_isDisabled = 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = _('Activación Cuenta');
        $message['text'][] = _('Su cuenta está pendiente de activación.');
        $message['text'][] = _('En breve recibirá un email de confirmación.');

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message, $this->userEmail);

        return TRUE;
    }
   
    /**
     * @brief Crear un usuario
     * @return bool
     */
    public function addUser() {
        $passdata = SP_Users::makeUserPass($this->userPass);

        $query = "INSERT INTO usrData SET "
                . "user_name = '" . DB::escape($this->userName) . "',"
                . "user_login = '" . DB::escape($this->userLogin) . "',"
                . "user_email = '" . DB::escape($this->userEmail) . "',"
                . "user_notes = '" . DB::escape($this->userNotes) . "',"
                . "user_groupId = " . (int) $this->userGroupId . ","
                . "user_profileId = " . (int) $this->userProfileId . ","
                . "user_isAdminApp = " . (int) $this->userIsAdminApp . ","
                . "user_isAdminAcc = " . (int) $this->userIsAdminAcc . ","
                . "user_isDisabled = " . (int) $this->userIsDisabled . ","
                . "user_pass = '" . $passdata['pass'] . "',"
                . "user_hashSalt = '" . $passdata['salt'] . "',"
                . "user_isLdap = 0";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $this->queryLastId = DB::$lastId;

        return TRUE;
    }
    
    /**
     * @brief Modificar un usuario
     * @return bool
     */
    public function updateUser() {
        $query = "UPDATE usrData SET "
                . "user_name = '" . DB::escape($this->userName) . "',"
                . "user_login = '" . DB::escape($this->userLogin) . "',"
                . "user_email = '" . DB::escape($this->userEmail) . "',"
                . "user_notes = '" . DB::escape($this->userNotes) . "',"
                . "user_groupId = " . (int) $this->userGroupId . ","
                . "user_profileId = " . (int) $this->userProfileId . ","
                . "user_isAdminApp = " . (int) $this->userIsAdminApp . ","
                . "user_isAdminAcc = " . (int) $this->userIsAdminAcc . ","
                . "user_isDisabled = " . (int) $this->userIsDisabled . ","
                . "user_lastUpdate = NOW() "
                . "WHERE user_id = " . (int) $this->userId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $this->queryLastId = DB::$lastId;

        return TRUE;
    }
    
    /**
     * @brief Modificar la clave de un usuario
     * @return bool
     */
    public function updateUserPass() {
        $passdata = SP_Users::makeUserPass($this->userPass);
        
        $query = "UPDATE usrData SET "
                . "user_pass = '" . $passdata['pass'] . "',"
                . "user_hashSalt = '" . $passdata['salt'] . "',"
                . "user_lastUpdate = NOW() "
                . "WHERE user_id = " . (int) $this->userId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $this->queryLastId = DB::$lastId;

        return TRUE;
    }
    
    /**
     * @brief Eliminar un usuario
     * @return bool
     */
    public function deleteUser() {
        $query = "DELETE FROM usrData "
                . "WHERE user_id = " . (int) $this->userId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $this->queryLastId = DB::$lastId;

        return TRUE;
    }

    /**
     * @brief Actualiza los datos de los usuarios de LDAP en la BBDD
     * @return bool
     */
    public function updateLDAPUserInDB() {
        $passdata = SP_Users::makeUserPass($this->userPass);

        $query = "UPDATE usrData SET "
                . "user_pass = '" . $passdata['pass'] . "',"
                . "user_hashSalt = '" . $passdata['salt'] . "',"
                . "user_name = '" . DB::escape($this->userName) . "',"
                . "user_email = '" . DB::escape($this->userEmail) . "',"
                . "user_lastUpdate = NOW() "
                . "WHERE user_id = " . $this->getUserIdByLogin($this->userLogin) . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Establece las variables de sesión del usuario
     * @return none
     */
    public function setUserSession() {
        $_SESSION['ulogin'] = $this->userLogin;
        $_SESSION['uprofile'] = $this->userProfileId;
        $_SESSION['uname'] = $this->userName;
        $_SESSION['ugroup'] = $this->userGroupId;
        $_SESSION['ugroupn'] = $this->userGroupName;
        $_SESSION['uid'] = $this->userId;
        $_SESSION['uemail'] = $this->userEmail;
        $_SESSION['uisadminapp'] = $this->userIsAdminApp;
        $_SESSION['uisadminacc'] = $this->userIsAdminAcc;
        $_SESSION['uisldap'] = $this->userIsLdap;
        $_SESSION['usrprofile'] = SP_Profiles::getProfileForUser();

        $this->setUserLastLogin();
    }

    /**
     * @brief Actualiza el último inicio de sesión del usuario en la BBDD
     * @return bool
     */
    private function setUserLastLogin() {
        $query = "UPDATE usrData SET "
                . "user_lastLogin = NOW(),"
                . "user_count = user_count + 1 "
                . "WHERE user_id = " . (int) $this->userId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }
    }

    /**
     * @brief Obtener el Id de usuario a partir del login
     * @return int con el Id del usuario
     */
    public static function getUserIdByLogin($login) {
        $query = "SELECT user_id "
                . "FROM usrData "
                . "WHERE user_login = '" . DB::escape($login) . "' LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return (int) $queryRes->user_id;
    }
    
    /**
     * @brief Obtener el login de usuario a partir del Id
     * @return string con el login del usuario
     */
    public static function getUserLoginById($id) {
        $query = "SELECT user_login "
                . "FROM usrData "
                . "WHERE user_id = " . (int)$id . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->user_login;
    }

    /**
     * @brief Comprueba la clave maestra del usuario
     * @return bool
     */
    public function checkUserMPass() {
        $userMPass = $this->getUserMPass(TRUE);

        if ($userMPass === FALSE) {
            return FALSE;
        }

        $configMPass = SP_Config::getConfigValue('masterPwd');

        if ($configMPass === FALSE) {
            return FALSE;
        }

        // Comprobamos el hash de la clave del usuario con la guardada
        return SP_Crypt::checkHashPass($userMPass, $configMPass);
    }

    /**
     * @brief Comprobar si el usuario tiene actualizada la clave maestra actual
     * @return bool
     */
    public static function checkUserUpdateMPass($login = '') {
        if (isset($login)) {
            $userId = self::getUserIdByLogin($login);
        }

        if (isset($_SESSION["uid"])) {
            $userId = $_SESSION["uid"];
        }

        if (!isset($userId)) {
            return FALSE;
        }

        $configMPassTime = SP_Config::getConfigValue('lastupdatempass');

        if ($configMPassTime === FALSE) {
            return FALSE;
        }

        $query = 'SELECT user_lastUpdateMPass '
                . 'FROM usrData '
                . 'WHERE user_id = ' . (int) $userId . ' LIMIT 1';
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        if ($configMPassTime > $queryRes->user_lastUpdateMPass) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Actualizar la clave maestra del usuario en la BBDD
     * @return bool
     */
    public function updateUserMPass($masterPwd) {
        $configMPass = SP_Config::getConfigValue('masterPwd');

        if (!$configMPass) {
            return FALSE;
        }

        if (SP_Crypt::checkHashPass($masterPwd, $configMPass)) {
            $crypt = new SP_Crypt;
            $strUserMPwd = $crypt->mkCustomMPassEncrypt($this->getCypherPass(), $masterPwd);

            if (!$strUserMPwd) {
                return FALSE;
            }
        } else {
            return FALSE;
        }

        $query = "UPDATE usrData SET "
                . "user_mPass = '".DB::escape($strUserMPwd[0])."',"
                . "user_mIV = '".DB::escape($strUserMPwd[1])."',"
                . "user_lastUpdateMPass = UNIX_TIMESTAMP() "
                . "WHERE user_id = " . (int) $this->userId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }
    
    /**
     * @brief Obtener una clave de cifrado basada en la clave del usuario y un salt
     * @return string con la clave de cifrado
     */
    private function getCypherPass(){
        $configSalt = SP_Config::getConfigValue('passwordsalt');
        $cypherPass = substr(sha1($configSalt.$this->userPass), 0, 32);
        
        return $cypherPass;
    }

    /**
     * @brief Desencriptar la clave maestra del usuario para la sesión
     * @param bool $showPass opcional, para devolver la clave desencriptada
     * @return bool|string Devuelve bool se hay error o string si se devuelve la clave
     */
    public function getUserMPass($showPass = FALSE) {
        $query = "SELECT user_mPass, user_mIV "
                . "FROM usrData "
                . "WHERE user_id = " . (int) $this->userId ." LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        if ($queryRes->user_mPass && $queryRes->user_mIV) {
            $crypt = new SP_Crypt;
            $clearMasterPass = $crypt->decrypt($queryRes->user_mPass, $this->getCypherPass(), $queryRes->user_mIV);

            if (!$clearMasterPass) {
                return FALSE;
            }

            if ($showPass == TRUE) {
                return $clearMasterPass;
            } else {
                $_SESSION['mPassPwd'] = substr(sha1(uniqid()),0,32);

                $sessionMasterPass = $crypt->mkCustomMPassEncrypt($_SESSION["mPassPwd"], $clearMasterPass);
                
                $_SESSION['mPass'] = $sessionMasterPass[0];
                $_SESSION['mPassIV'] = $sessionMasterPass[1];
                return TRUE;
            }
        }
        return FALSE;
    }
    
    /**
     * @brief Obtiene el listado de grupos de una cuenta
     * @return object con el Id de grupo
     */
    public static function getUsersForAccount($accountId) {
        $query = "SELECT accuser_userId "
                . "FROM accUsers "
                . "WHERE accuser_accountId = " . (int) $accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes;
    }
    
    /**
     * @brief Obtiene el listado con el nombre de los usuarios de una cuenta
     * @return array con los nombres de los usuarios ordenados
     */
    public static function getUsersNameForAccount($accountId) {
        $query = "SELECT user_id,"
                . "user_login "
                . "FROM accUsers "
                . "JOIN usrData ON user_Id = accuser_userId "
                . "WHERE accuser_accountId = " . (int) $accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        foreach ($queryRes as $users) {
            $usersName[$users->user_id] = $users->user_login;
        }

        asort($usersName, SORT_STRING);

        return $usersName;
    }
    
    /**
     * @brief Actualizar la asociación de grupos con cuentas
     * @param int $accountId con el Id de la cuenta
     * @param array $newGroups con los grupos de la cuenta
     * @return bool
     */
    public static function updateUsersForAccount($accountId, $usersId) {
        if (self::deleteUsersForAccount($accountId, $usersId)) {
            return self::addUsersForAccount($accountId, $usersId);
        }

        return FALSE;
    }

    /**
     * @brief Eliminar la asociación de grupos con cuentas
     * @param int $accountId con el Id de la cuenta
     * @param array $usersId opcional con los grupos de la cuenta
     * @return bool
     */
    public static function deleteUsersForAccount($accountId, $usersId = NULL) {
        $queryExcluded = '';

        // Excluimos los grupos actuales
        if (is_array($usersId)) {
            $queryExcluded = ' AND accuser_userId NOT IN ('.  implode(',', $usersId).')';
        }

        $query = 'DELETE FROM accUsers '
                . 'WHERE accuser_accountId = ' . (int) $accountId . $queryExcluded;

        error_log($query);
        
        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Crear asociación de grupos con cuentas
     * @param int $accountId con el Id de la cuenta
     * @param array $usersId con los grupos de la cuenta
     * @return bool
     */
    public static function addUsersForAccount($accountId, $usersId) {
        $values = '';

        // Obtenemos los grupos actuales
        $currentUsers = self::getUsersForAccount($accountId);
        
        if (is_array($currentUsers) ){
            foreach ( $currentUsers as $user ){
                $usersExcluded[] = $user->accuser_userId;
            }
        }
        
        foreach ($usersId as $userId) {
            // Excluimos los usuarios actuales
            if ( is_array($usersExcluded) && in_array($userId, $usersExcluded)){
                continue;
            }
            
            $values[] = '(' . $accountId . ',' . $userId . ')';
        }

        if ( ! is_array($values) ){
            return TRUE;
        }
        
        $query = 'INSERT INTO accUsers (accuser_accountId, accuser_userId) '
                . 'VALUES ' . implode(',', $values);

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }
}