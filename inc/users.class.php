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
    // Variables de grupos
    var $groupId;
    var $groupName;
    var $groupDesc;
    // Variables de Perfil
    var $profileId;
    var $profileName;
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
        $query = "SELECT user_id, user_name, user_groupId, user_login, user_email, user_notes, user_count, user_profileId,
                    usergroup_name, user_isAdminApp, user_isAdminAcc, user_isLdap, user_isDisabled 
                    FROM usrData
                    LEFT JOIN usrGroups ON user_groupId = usergroup_id 
                    LEFT JOIN usrProfiles ON user_profileId = userprofile_id 
                    WHERE user_login = '" . DB::escape($this->userLogin) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }


        $this->userId = (int) $queryRes[0]->user_id;
        $this->userName = $queryRes[0]->user_name;
        $this->userGroupId = (int) $queryRes[0]->user_groupId;
        $this->userGroupName = $queryRes[0]->usergroup_name;
        $this->userEmail = $queryRes[0]->user_email;
        $this->userProfileId = (int) $queryRes[0]->user_profileId;
        $this->userIsAdminApp = (int) $queryRes[0]->user_isAdminApp;
        $this->userIsAdminAcc = (int) $queryRes[0]->user_isAdminAcc;
        $this->userIsLdap = (int) $queryRes[0]->user_isLdap;

        return TRUE;
    }

    /**
     * @brief Obtener los detalles de una consulta para generar una tabla
     * @return bool
     */
    public static function getItemDetail() {
        if (!isset(self::$querySelect) || self::$querySelect == "") {
            return FALSE;
        }
        if (!isset(self::$queryFrom) || self::$queryFrom == "") {
            return FALSE;
        }

        $query = self::$querySelect . ' ' . self::$queryFrom . ' ' . self::$queryWhere;
        self::$queryRes = DB::getResults($query, __FUNCTION__);

        if (self::$queryRes === FALSE || !is_array(self::$queryRes)) {
            return FALSE;
        }

        self::$queryCount = count(self::$queryRes);

        if (self::$queryCount === 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Establecer las variables para la consulta de usuarios
     * @param int $itemId opcional, con el Id del usuario a consultar
     * @return none
     */
    public static function setQueryUsers($itemId = NULL) {
        if (!is_null($itemId)) {
            self::$querySelect = "SELECT user_id, user_name, user_login, user_profileId, user_groupId, user_email, user_notes, 
                                    user_isAdminApp, user_isAdminAcc, user_isLdap, user_isDisabled";
            self::$queryWhere = "WHERE user_id = " . (int) $itemId . " LIMIT 1";
        } else {
            self::$querySelect = "SELECT user_id, user_name, user_login, userprofile_name, usergroup_name,  user_isAdminApp, 
                                    user_isAdminAcc, user_isLdap, user_isDisabled";
            self::$queryWhere = ( $_SESSION["uisadminapp"] == 0 ) ? "WHERE user_isAdminApp = 0 ORDER BY user_name" : "ORDER BY user_name";
        }

        self::$queryFrom = "FROM usrData 
                                LEFT JOIN usrProfiles ON user_profileId = userprofile_id
                                LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id";
    }

    /**
     * @brief Establecer las variables para la consulta de grupos
     * @param int $itemId opcional, con el Id del grupo a consultar
     * @return none
     */
    public static function setQueryUserGroups($itemId = NULL) {
        if (!is_null($itemId)) {
            self::$querySelect = "SELECT usergroup_id, usergroup_name, usergroup_description";
            self::$queryWhere = "WHERE usergroup_id = " . (int) $itemId . " LIMIT 1";
        } else {
            self::$querySelect = "SELECT usergroup_id, usergroup_name, usergroup_description";
            self::$queryWhere = "ORDER BY usergroup_name";
        }

        self::$queryFrom = "FROM usrGroups";
    }

    /**
     * @brief Establecer las variables para la consulta de perfiles
     * @param int $itemId opcional, con el Id del usuario a perfiles
     * @return none
     */
    public static function setQueryUserProfiles($itemId = NULL) {
        if (!is_null($itemId)) {
            self::$querySelect = "SELECT userprofile_id, userprofile_name, userProfile_pView, userProfile_pViewPass,
                                    userProfile_pViewHistory, userProfile_pEdit, userProfile_pEditPass, userProfile_pAdd,
                                    userProfile_pDelete, userProfile_pFiles, userProfile_pConfig, userProfile_pConfigCategories,
                                    userProfile_pConfigMasterPass, userProfile_pConfigBackup, userProfile_pUsers, 
                                    userProfile_pGroups, userProfile_pProfiles, userProfile_pEventlog";
            self::$queryWhere = "WHERE userprofile_id = " . (int) $itemId . " LIMIT 1";
        } else {
            self::$querySelect = "SELECT userprofile_id, userprofile_name";
            self::$queryWhere = "ORDER BY userprofile_name";
        }

        self::$queryFrom = "FROM usrProfiles";
    }

    /**
     * @brief Obtener los datos para generar un select
     * @param string $tblName con el nombre de la tabla a cunsultar
     * @param string $tblColId con el nombre de la columna a mostrar
     * @param array $arrFilter con las columnas a filtrar
     * @return array con los valores del select con el Id como clave y el nombre como valor
     */
    public static function getValuesForSelect($tblName, $tblColId, $tblColName, $arrFilter = "") {
        if (!$tblName || !$tblColId || !$tblColName) {
            return;
        }

        $strFilter = ( is_array($arrFilter) ) ? " WHERE " . implode(" OR ", $arrFilter) : "";

        $query = "SELECT $tblColId, $tblColName FROM $tblName $strFilter";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        $arrValues = array();

        foreach ($queryRes as $row) {
            $arrValues[$row->$tblColId] = $row->$tblColName;
        }

        return $arrValues;
    }

    /**
     * @brief Devolver la tabla de usuarios, grupos o perfiles
     * @param array $arrUsersTableProp con las propiedades de la tabla
     * @return none
     */
    public static function getUsrGrpTable($arrUsersTableProp) {
        $sk = SP_Common::getSessionKey(TRUE);

        echo '<div class="action fullWidth">';
        echo '<ul>';
        echo '<LI><img src="imgs/add.png" title="' . _('Nuevo') . '" class="inputImg" OnClick="usrgrpDetail(0,' . $arrUsersTableProp["newActionId"] . ',\'' . $sk . '\',' . $arrUsersTableProp["active"] . ');" /></LI>';
        echo '</ul>';
        echo '</div>';

        if ($arrUsersTableProp["header"]) {
            echo '<div id="title" class="midroundup titleNormal">' . $arrUsersTableProp["header"] . '</div>';
        }

        echo '<form name="' . $arrUsersTableProp["frmId"] . '" id="' . $arrUsersTableProp["frmId"] . '" OnSubmit="return false;" >';
        echo '<div id="' . $arrUsersTableProp["tblId"] . '" class="data-header" >';
        echo '<ul class="round header-grey">';

        $cellWidth = floor(85 / count($arrUsersTableProp["tblHeaders"]));

        foreach ($arrUsersTableProp["tblHeaders"] as $header) {
            if (is_array($header)) {
                echo '<li class="' . $header['class'] . '" style="width: ' . $cellWidth . '%;">' . $header['name'] . '</li>';
            } else {
                echo '<li style="width: ' . $cellWidth . '%;">' . $header . '</li>';
            }
        }

        echo '</ul>';
        echo '</div>';

        echo '<div class="data-rows">';

        foreach (self::$queryRes as $item) {
            $intId = $item->$arrUsersTableProp["tblRowSrcId"];
            $action_check = array();

            $lnkEdit = '<img src="imgs/edit.png" title="' . _('Editar') . '" class="inputImg" Onclick="return usrgrpDetail(' . $intId . ',' . $arrUsersTableProp["actionId"] . ',\'' . $sk . '\', ' . $arrUsersTableProp["active"] . ');" />';
            $lnkDel = '<img src="imgs/delete.png" title="' . _('Eliminar') . '" class="inputImg" Onclick="return usersMgmt(' . $arrUsersTableProp["active"] . ', 1,' . $intId . ',' . $arrUsersTableProp["actionId"] . ',\'' . $sk . '\', ' . $arrUsersTableProp["active"] . ');" />';
            $lnkPass = '<img src="imgs/key.png" title="' . _('Cambiar clave') . '" class="inputImg" Onclick="return usrUpdPass(' . $intId . ');" />';

            echo '<ul>';

            foreach ($arrUsersTableProp["tblRowSrc"] as $rowSrc) {
                // If row is an array handle images in it
                if (is_array($rowSrc)) {
                    echo '<li class="cell-nodata" style="width: ' . $cellWidth . '%;">';
                    foreach ($rowSrc as $rowName => $imgProp) {
                        if ($item->$rowName) {
                            echo '<img src="imgs/' . $imgProp['img_file'] . '" title="' . $imgProp['img_title'] . '" />';
                            $action_check[$rowName] = 1;
                        }
                    }
                    echo '</li>';
                } else {
                    echo '<li class="cell-data" style="width: ' . $cellWidth . '%;">';
                    echo ( $item->$rowSrc ) ? $item->$rowSrc : '&nbsp;'; // Fix height
                    echo '</li>';
                }
            }

            echo '<li class="cell-actions round" style="width: ' . $cellWidth . '%;">';
            foreach ($arrUsersTableProp["actions"] as $action) {
                switch ($action) {
                    case "edit":
                        echo $lnkEdit;
                        break;
                    case "del":
                        echo $lnkDel;
                        break;
                    case "pass":
                        echo (!isset($action_check['user_isLdap']) ) ? $lnkPass : '';
                        break;
                }
            }
            echo '</li>';
            echo '</ul>';
        }

        echo '</div></form>';
    }

    /**
     * @brief Obtener los datos de un usuario
     * @param int $id con el Id del usuario a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getUserData($id = 0) {
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
            'action' => 1);

        if ($id > 0) {
            self::setQueryUsers($id);

            if (self::getItemDetail()) {
                $user['checks'] = array();

                foreach (self::$queryRes as $row) {
                    foreach ($row as $name => $value) {
                        // Check if field is a checkbox one
                        if (preg_match('/^.*_is[A-Z].*$/', $name)) {
                            $user['checks'][$name] = ( (int) $value === 1 ) ? 'CHECKED' : '';
                        }

                        $user[$name] = $value;
                    }
                }

                $user['action'] = 2;
            }
        }

        return $user;
    }

    /**
     * @brief Obtener los datos de un grupo
     * @param int $id con el Id del grupo a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getGroupData($id = 0) {
        $group = array('usergroup_id' => 0,
            'usergroup_name' => '',
            'usergroup_description' => '',
            'action' => 1);

        if ($id > 0) {
            self::setQueryUserGroups($id);

            if (self::getItemDetail()) {
                foreach (self::$queryRes as $row) {
                    foreach ($row as $name => $value) {
                        $group[$name] = $value;
                    }
                }
                $group['action'] = 2;
            }
        }

        return $group;
    }

    /**
     * @brief Obtener los datos de un perfil
     * @param int $id con el Id del perfil a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getProfileData($id = 0) {

        $profile = array('userprofile_id' => 0,
            'userprofile_name' => '',
            'userProfile_pView' => 0,
            'userProfile_pViewPass' => 0,
            'userProfile_pViewHistory' => 0,
            'userProfile_pEdit' => 0,
            'userProfile_pEditPass' => 0,
            'userProfile_pAdd' => 0,
            'userProfile_pDelete' => 0,
            'userProfile_pFiles' => 0,
            'userProfile_pConfig' => 0,
            'userProfile_pConfigCategories' => 0,
            'userProfile_pConfigMasterPass' => 0,
            'userProfile_pConfigBackup' => 0,
            'userProfile_pUsers' => 0,
            'userProfile_pGroups' => 0,
            'userProfile_pProfiles' => 0,
            'userProfile_pEventlog' => 0,
            'action' => 1);

        if ($id > 0) {
            self::setQueryUserProfiles($id);

            if (self::getItemDetail()) {
                foreach (self::$queryRes as $row) {
                    foreach ($row as $name => $value) {
                        if (preg_match('/^.*_p[A-Z].*$/', $name)) {
                            $profile[$name] = ( (int) $value === 1 ) ? "CHECKED" : "";
                        } else {
                            $profile[$name] = $value;
                        }
                    }
                }

                $profile['action'] = 2;
            }
        }

        return $profile;
    }

    /**
     * @brief Comprobar si un usuario/email existen en la BBDD
     * @return bool|int Devuelve bool si error y int si existe el usuario/email
     */
    public function checkUserExist() {
        $userLogin = strtoupper($this->userLogin);
        $userEmail = strtoupper($this->userEmail);

        $query = "SELECT user_login, user_email FROM usrData
                    WHERE (UPPER(user_login) = '" . DB::escape($userLogin) . "' 
                    OR UPPER(user_email) = '" . DB::escape($userEmail) . "') 
                    AND user_id != " . (int) $this->userId;
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
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
     * @brief Comprobar si un grupo existe en la BBDD
     * @return bool
     */
    public function checkGroupExist() {
        $strGroupName = strtoupper($this->groupName);

        if ($this->groupId) {
            $query = "SELECT usergroup_name FROM usrGroups
                        WHERE UPPER(usergroup_name) = '" . DB::escape($strGroupName) . "' 
                        AND usergroup_id != " . (int) $this->groupId;
        } else {
            $query = "SELECT usergroup_name FROM usrGroups
                        WHERE UPPER(usergroup_name) = '" . DB::escape($strGroupName) . "'";
        }

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        if (count(DB::$last_result) >= 1) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si un perfil existe
     * @return bool
     */
    public function checkProfileExist() {
        $profileName = strtoupper($this->profileName);

        if ($this->profileId) {
            $query = "SELECT userprofile_name FROM usrProfiles
                        WHERE UPPER(userprofile_name) = '" . DB::escape($profileName) . "' 
                        AND userprofile_id != " . (int) $this->profileId;
        } else {
            $query = "SELECT userprofile_name FROM usrProfiles
                        WHERE UPPER(userprofile_name) = '" . DB::escape($profileName) . "'";
        }

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        if (count(DB::$last_result) >= 1) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si un grupo está en uso
     * @return bool
     * 
     * Esta función comprueba si un grupo está en uso por usuarios o cuentas.
     */
    public function checkGroupInUse() {
        // Número de usuarios con el grupo
        $query = "SELECT user_id FROM usrData WHERE user_groupId = " . (int) $this->groupId;

        if (DB::doQuery($query, __FUNCTION__) === FALSE){
            return FALSE;
        }

        $numRows = count(DB::$last_result);
        $txt = _('Usuarios') . " (" . $numRows . ")";

        if ($numRows) {
            return $txt;
        }

        // Número de cuentas con el grupo como primario
        $query = "SELECT account_id FROM accounts WHERE account_userGroupId = " . (int) $this->groupId;

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $numRows = count(DB::$last_result);
        $txt = _('Cuentas') . " (" . $numRows . ")";

        if ($numRows) {
            return $txt;
        }

        // Número de cuentas con el grupo como secundario
        $query = "SELECT accgroup_id FROM accGroups WHERE accgroup_groupId = " . (int) $this->groupId;

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $numRows = count(DB::$last_result);
        $txt = _('Cuentas') . " (" . $numRows . ")";

        if ($numRows) {
            return $txt;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si un perfil está en uso
     * @return bool
     */
    public function checkProfileInUse() {
        $query = "SELECT user_id FROM usrData WHERE user_profileId = " . (int) $this->profileId;

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $numRows = count(DB::$last_result);

        if ($numRows >= 1) {
            return "Usuarios ($numRows)";
        }

        return TRUE;
    }

    /**
     * @brief Comprobar la clave de usuario en la BBDD
     * @return bool
     * 
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     */
    public function checkUserPass() {
        if ($this->checkUserIsMigrate()) {
            if (!$this->migrateUser()) {
                return FALSE;
            }
        }

        $query = "SELECT user_login, user_pass FROM usrData
                        WHERE user_login = '" . DB::escape($this->userLogin) . "'
                        AND user_isMigrate = 0
                        AND user_pass = SHA1(CONCAT(user_hashSalt,'" . DB::escape($this->userPass) . "')) LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        if (count(DB::$last_result) == 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si un usuario está deshabilitado
     * @return bool
     */
    public function checkUserIsDisabled() {
        $query = "SELECT user_isDisabled FROM usrData
                    WHERE user_login = '" . DB::escape($this->userLogin) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        if ($queryRes[0]->user_isDisabled == 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si los datos del usuario de LDAP están en la BBDD
     * @return bool
     */
    public function checkUserLDAP() {
        $query = "SELECT user_login FROM usrData 
                    WHERE user_login = '" . DB::escape($this->userLogin) . "' LIMIT 1";

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
    private function checkUserIsMigrate() {
        $query = "SELECT user_isMigrate FROM usrData
                    WHERE user_login = '" . DB::escape($this->userLogin) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        if ($queryRes[0]->user_isMigrate == 0) {
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
    private function migrateUser() {
        $passdata = $this->makeUserPass();

        $query = "UPDATE usrData SET 
                    user_pass = '" . $passdata['pass'] . "',
                    user_hashSalt = '" . $passdata['salt'] . "',
                    user_lastUpdate = NOW(),
                    user_isMigrate = 0
                    WHERE user_login = '" . DB::escape($this->userLogin) . "'
                    AND user_isMigrate = 1
                    AND (user_pass = SHA1(CONCAT(user_hashSalt,'" . DB::escape($this->userPass) . "'))
                    OR user_pass = MD5('" . DB::escape($this->userPass) . "')) LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = __FUNCTION__;
        $message['text'][] = _('Usuario actualizado');
        $message['text'][] = 'Login: ' . $this->userLogin;

        SP_Common::wrLogInfo($message);
        return TRUE;
    }

    /**
     * @brief Crear la clave de un usuario
     * @return array con la clave y salt del usuario
     */
    private function makeUserPass() {
        $salt = SP_Crypt::makeHashSalt();
        $userPass = DB::escape(sha1($salt . DB::escape($this->userPass)));

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
        $passdata = $this->makeUserPass();

        $query = "INSERT INTO usrData SET
                    user_name = '" . DB::escape($this->userName) . "',
                    user_groupId = 0,
                    user_login = '" . DB::escape($this->userLogin) . "',
                    user_pass = '" . $passdata['pass'] . "',
                    user_hashSalt = " . $passdata['salt'] . ",
                    user_email = '" . DB::escape($this->userEmail) . "',
                    user_notes = 'LDAP',
                    user_profileId = 0,
                    user_isLdap = 1,
                    user_isDisabled = 1";

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
     * @brief Gestión de los datos de los usuario de la BBDD
     * @param string $strAction con la acción a realizar
     * @return bool
     * 
     * Esta función realiza las operaciones de alta, modificación, eliminación y cambio de clave
     * de los usuarios de la BBDD
     */
    public function manageUser($actionName) {
        $passdata = $this->makeUserPass();

        switch ($actionName) {
            case "add":
                $query = "INSERT INTO usrData SET
                            user_name = '" . DB::escape($this->userName) . "',
                            user_login = '" . DB::escape($this->userLogin) . "',
                            user_email = '" . DB::escape($this->userEmail) . "',
                            user_notes = '" . DB::escape($this->userNotes) . "',
                            user_groupId = " . (int) $this->userGroupId . ",
                            user_profileId = " . (int) $this->userProfileId . ",
                            user_isAdminApp = " . (int) $this->userIsAdminApp . ",
                            user_isAdminAcc = " . (int) $this->userIsAdminAcc . ",
                            user_pass = '" . $passdata['pass'] . "',
                            user_hashSalt = '" . $passdata['salt'] . "',
                            user_isLdap = 0";
                break;
            case "update":
                $query = "UPDATE usrData SET
                            user_name = '" . DB::escape($this->userName) . "',
                            user_login = '" . DB::escape($this->userLogin) . "',
                            user_email = '" . DB::escape($this->userEmail) . "',
                            user_notes = '" . DB::escape($this->userNotes) . "',
                            user_groupId = " . (int) $this->userGroupId . ",
                            user_profileId = " . (int) $this->userProfileId . ",
                            user_isAdminApp = " . (int) $this->userIsAdminApp . ", 
                            user_isAdminAcc = " . (int) $this->userIsAdminAcc . ", 
                            user_isDisabled = " . (int) $this->userIsDisabled . ",
                            user_lastUpdate = NOW()
                            WHERE user_id = " . (int) $this->userId . " LIMIT 1";
                break;
            case "updatepass":
                $query = "UPDATE usrData SET 
                            user_pass = '" . $passdata['pass'] . "',
                            user_hashSalt = '" . $passdata['salt'] . "',
                            user_lastUpdate = NOW()
                            WHERE user_id = " . (int) $this->userId . " LIMIT 1";
                break;
            case "delete":
                $query = "DELETE FROM usrData WHERE user_id = " . (int) $this->userId . " LIMIT 1";
                break;
            default :
                return FALSE;
        }

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $this->queryLastId = DB::$lastId;

        return TRUE;
    }

    /**
     * @brief Gestión de los datos de los grupos de la BBDD
     * @param string $actionName con la acción a realizar
     * @return bool
     * 
     * Esta función realiza las operaciones de alta, modificación y eliminación
     * de los grupos de la BBDD
     */
    public function manageGroup($actionName) {
        switch ($actionName) {
            case "add":
                $query = "INSERT INTO usrGroups SET
                            usergroup_name = '" . DB::escape($this->groupName) . "',
                            usergroup_description = '" . DB::escape($this->groupDesc) . "'";
                break;
            case "update":
                $query = "UPDATE usrGroups SET 
                            usergroup_name = '" . DB::escape($this->groupName) . "',
                            usergroup_description = '" . $this->groupDesc . "' 
                            WHERE usergroup_id = " . (int) $this->groupId;
                break;
            case "delete":
                $query = "DELETE FROM usrGroups WHERE usergroup_id = " . (int) $this->groupId . " LIMIT 1";
                break;
            default :
                return FALSE;
        }

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $this->queryLastId = DB::$lastId;

        return TRUE;
    }

    /**
     * @brief Gestión de los datos de los perfiles de la BBDD
     * @param string $actionName con la acción a realizar
     * @param array $profileProp con los permisos sobre los perfiles de usuarios y configuración
     * @return bool
     * 
     * Esta función realiza las operaciones de alta, modificación y eliminación
     * de los perfiles de la BBDD
     */
    public function manageProfiles($actionName, $profileProp = "") {
        $enableConfig = (int) ( $profileProp["pConfig"] || $profileProp["pConfigCat"] || $profileProp["pConfigMpw"] || $profileProp["pConfigBack"]);
        $enableusers = (int) ( $profileProp["pUsers"] || $profileProp["pGroups"] || $profileProp["pProfiles"]);

        switch ($actionName) {
            case "add":
                $query = "INSERT INTO usrProfiles SET
                            userprofile_name = '" . DB::escape($this->profileName) . "',
                            userProfile_pView = " . $profileProp["pAccView"] . ",
                            userProfile_pViewPass = " . $profileProp["pAccViewPass"] . ",
                            userProfile_pViewHistory = " . $profileProp["pAccViewHistory"] . ",
                            userProfile_pEdit = " . $profileProp["pAccEdit"] . ",
                            userProfile_pEditPass = " . $profileProp["pAccEditPass"] . ",
                            userProfile_pAdd = " . $profileProp["pAccAdd"] . ",
                            userProfile_pDelete = " . $profileProp["pAccDel"] . ",
                            userProfile_pFiles = " . $profileProp["pAccFiles"] . ",
                            userProfile_pConfigMenu = " . $enableConfig . ",
                            userProfile_pConfig = " . $profileProp["pConfig"] . ",
                            userProfile_pConfigCategories = " . $profileProp["pConfigCat"] . ",
                            userProfile_pConfigMasterPass = " . $profileProp["pConfigMpw"] . ",
                            userProfile_pConfigBackup = " . $profileProp["pConfigBack"] . ",
                            userProfile_pUsersMenu = " . $enableusers . ",
                            userProfile_pUsers = " . $profileProp["pUsers"] . ",
                            userProfile_pGroups = " . $profileProp["pGroups"] . ",
                            userProfile_pProfiles = " . $profileProp["pProfiles"] . ",
                            userProfile_pEventlog = " . $profileProp["pEventlog"];
                break;
            case "update":
                $query = "UPDATE usrProfiles SET
                            userprofile_name = '" . DB::escape($this->profileName) . "',
                            userProfile_pView = " . $profileProp["pAccView"] . ",
                            userProfile_pViewPass = " . $profileProp["pAccViewPass"] . ",
                            userProfile_pViewHistory = " . $profileProp["pAccViewHistory"] . ",
                            userProfile_pEdit = " . $profileProp["pAccEdit"] . ",
                            userProfile_pEditPass = " . $profileProp["pAccEditPass"] . ",
                            userProfile_pAdd = " . $profileProp["pAccAdd"] . ",
                            userProfile_pDelete = " . $profileProp["pAccDel"] . ",
                            userProfile_pFiles = " . $profileProp["pAccFiles"] . ",
                            userProfile_pConfigMenu = " . $enableConfig . ",
                            userProfile_pConfig = " . $profileProp["pConfig"] . ",
                            userProfile_pConfigCategories = " . $profileProp["pConfigCat"] . ",
                            userProfile_pConfigMasterPass = " . $profileProp["pConfigMpw"] . ",
                            userProfile_pConfigBackup = " . $profileProp["pConfigBack"] . ",
                            userProfile_pUsersMenu = " . $enableusers . ",
                            userProfile_pUsers = " . $profileProp["pUsers"] . ",
                            userProfile_pGroups = " . $profileProp["pGroups"] . ",
                            userProfile_pProfiles = " . $profileProp["pProfiles"] . ",
                            userProfile_pEventlog = " . $profileProp["pEventlog"] . "
                            WHERE userprofile_id = " . (int) $this->profileId . " LIMIT 1";
                break;
            case "delete":
                $query = "DELETE FROM usrProfiles WHERE userprofile_id = " . (int) $this->profileId . " LIMIT 1";
                break;
            default :
                return FALSE;
        }

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
    public function updateUserLDAP() {
        $passdata = $this->makeUserPass();

        $query = "UPDATE usrData SET "
                . "user_pass = '" . $passdata['pass'] . "',"
                . "user_hashSalt = '" . $passdata['salt'] . "',"
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
        $_SESSION['usrprofile'] = self::getUserProfile();

        $this->setUserLastLogin();
    }

    /**
     * @brief Actualiza el último inicio de sesión del usuario en la BBDD
     * @return bool
     */
    private function setUserLastLogin() {
        $query = "UPDATE usrData SET "
                . "user_lastLogin = NOW(), "
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
        $query = "SELECT user_id FROM usrData 
                    WHERE user_login = '" . DB::escape($login) . "' LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        return (int) $queryRes[0]->user_id;
    }
    
    /**
     * @brief Obtener el login de usuario a partir del Id
     * @return string con el login del usuario
     */
    public static function getUserLoginById($id) {
        $query = "SELECT user_login FROM usrData 
                    WHERE user_id = " . (int)$id . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        return $queryRes[0]->user_login;
    }
    
    /**
     * @brief Obtener el nombre de un grupo por a partir del Id
     * @return string con el nombre del grupo
     */
    public static function getGroupNameById($id) {
        $query = "SELECT usergroup_name FROM usrGroups 
                    WHERE usergroup_id = " . (int)$id . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        return $queryRes[0]->usergroup_name;
    }
    
    /**
     * @brief Obtener el nombre de un perfil por a partir del Id
     * @return string con el nombre del perfil
     */
    public static function getProfileNameById($id) {
        $query = "SELECT userprofile_name FROM usrProfiles 
                    WHERE userprofile_id = " . (int)$id . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        return $queryRes[0]->userprofile_name;
    }

    /**
     * @brief Autentifica al usuario con LDAP
     * @return bool
     */
    public function authUserLDAP() {
        if (SP_Config::getValue('ldapenabled', 0) === 0 || !SP_Util::ldapIsAvailable()) {
            return FALSE;
        }

        $searchBase = SP_Config::getValue('ldapbase');
        $ldapserver = SP_Config::getValue('ldapserver');
        $ldapgroup = SP_Config::getValue('ldapgroup');
        $bindDN = SP_Config::getValue('ldapbinduser');
        $bindPass = SP_Config::getValue('ldapbindpass');

        if (!$searchBase || !$ldapserver || !$ldapgroup || !$bindDN || !$bindPass) {
            return FALSE;
        }

        $ldapAccess = FALSE;
        $message['action'] = __FUNCTION__;

        // Conexión al servidor LDAP
        if (!$ldapConn = @ldap_connect($ldapserver)) {
            $message['text'][] = _('No es posible conectar con el servidor de LDAP') . " '" . $ldapserver . "'";
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        @ldap_set_option($ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version

        if (!@ldap_bind($ldapConn, $bindDN, $bindPass)) {
            $message['text'][] = _('Error al conectar (bind)');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        $filter = "(&(|(samaccountname=$this->userLogin)(cn=$this->userLogin))(|(objectClass=inetOrgPerson)(objectClass=person)))";
        $filterAttr = array("dn", "displayname", "samaccountname", "mail", "memberof", "lockouttime", "fullname", "groupmembership", "mail");

        $searchRes = @ldap_search($ldapConn, $searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar el DN del usuario');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        if (@ldap_count_entries($ldapConn, $searchRes) === 1) {
            $searchUser = @ldap_get_entries($ldapConn, $searchRes);

            if (!$searchUser) {
                $message['text'][] = _('Error al localizar el usuario en LDAP');
                $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

                SP_Common::wrLogInfo($message);
                return FALSE;
            }

            $userDN = $searchUser[0]["dn"];
        }

        if (@ldap_bind($ldapConn, $userDN, $this->userPass)) {
            @ldap_unbind($ldapConn);

            foreach ($searchUser as $entryValue) {
                if (is_array($entryValue)) {
                    foreach ($entryValue as $entryAttr => $attrValue) {
                        if (is_array($attrValue)) {
                            if ($entryAttr == "groupmembership" || $entryAttr == "memberof") {
                                foreach ($attrValue as $group) {
                                    if (is_int($group)) {
                                        continue;
                                    }

                                    preg_match('/^cn=([\w\s-]+),.*/i', $group, $groupName);

                                    // Comprobamos que el usuario está en el grupo indicado
                                    if ($groupName[1] == $ldapgroup || $group == $ldapgroup) {
                                        $ldapAccess = TRUE;
                                        break;
                                    }
                                }
                            } elseif ($entryAttr == "displayname" | $entryAttr == "fullname") {
                                $this->userName = $attrValue[0];
                            } elseif ($entryAttr == "mail") {
                                $this->userEmail = $attrValue[0];
                            } elseif ($entryAttr == "lockouttime") {
                                if ($attrValue[0] > 0)
                                    return FALSE;
                            }
                        }
                    }
                }
            }
        } else {
            $message['text'][] = _('Error al conectar con el usuario');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return ldap_errno($ldapConn);
        }

        return $ldapAccess;
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

        $query = 'SELECT user_lastUpdateMPass FROM usrData WHERE user_id = ' . $userId;
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        foreach ($queryRes as $userData) {
            $userLastUpdateMPass = $userData->user_lastUpdateMPass;
        }

        if ($configMPassTime > $userLastUpdateMPass) {
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

        $query = "UPDATE usrData SET
                        user_mPass = '".DB::escape($strUserMPwd[0])."',
                        user_mIV = '".DB::escape($strUserMPwd[1])."',
                        user_lastUpdateMPass = UNIX_TIMESTAMP()
                        WHERE user_id = " . (int) $this->userId . " LIMIT 1";

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

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        $userData = $queryRes[0];
                
        if ($userData->user_mPass && $userData->user_mIV) {
            $crypt = new SP_Crypt;
            $clearMasterPass = $crypt->decrypt($userData->user_mPass, $this->getCypherPass(), $userData->user_mIV);

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
     * @brief Obtener el perfil de un usuario
     * @return object con los permisos del perfil del usuario
     */
    public static function getUserProfile() {
        $userId = SP_Common::parseParams('s', 'uid', 0);
        
        if ( ! $userId ){
            return FALSE;
        }
        
        $query = "SELECT user_profileId,
                            userProfile_pView,
                            userProfile_pViewPass,
                            userProfile_pViewHistory,
                            userProfile_pEdit,
                            userProfile_pEditPass,
                            userProfile_pAdd,
                            userProfile_pDelete,
                            userProfile_pFiles,
                            userProfile_pConfigMenu,
                            userProfile_pConfig,
                            userProfile_pConfigCategories,
                            userProfile_pConfigMasterPass,
                            userProfile_pConfigBackup,
                            userProfile_pUsersMenu,
                            userProfile_pUsers,
                            userProfile_pGroups,
                            userProfile_pProfiles,
                            userProfile_pEventlog 
                            FROM usrData 
                            JOIN usrProfiles ON userProfile_Id = user_profileId
                            WHERE user_id = " . $userId . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE || !is_array($queryRes)) {
            return FALSE;
        }

        return $queryRes[0];
    }

    /**
     * @brief Comprobar los permisos de acceso del usuario a los módulos de la aplicación
     * @param string $strAction con el nombre de la acción
     * @param int $userId opcional, con el Id del usuario
     * @return bool
     * 
     * Esta función comprueba los permisos del usuario para realizar una acción.
     * Si los permisos ya han sido obtenidos desde la BBDD, se utiliza el objeto creado
     * en la variable de sesión.
     */
    public static function checkUserAccess($strAction, $userId = 0) {
        // Comprobamos si la cache de permisos está inicializada
        if (!isset($_SESSION["usrprofile"]) || !is_object($_SESSION["usrprofile"])) {
            return FALSE;
        }

        $blnUIsAdminApp = $_SESSION["uisadminapp"];
        $blnUIsAdminAcc = $_SESSION["uisadminacc"];
        $profile = $_SESSION["usrprofile"];

        switch ($strAction) {
            case "accview":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pView );
                break;
            case "accviewpass":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pViewPass );
                break;
            case "accviewhistory":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pViewHistory );
                break;
            case "accedit":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pEdit );
                break;
            case "acceditpass":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pEditPass || $userId == $_SESSION["uid"] );
                break;
            case "accnew":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pAdd );
                break;
            case "acccopy":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || ($profile->userProfile_pAdd && $profile->userProfile_pView) );
                break;
            case "accdelete":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pDelete );
                break;
            case "accfiles":
                return ( $blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pFiles );
                break;
            case "configmenu":
                return ( $blnUIsAdminApp || $profile->userProfile_pConfigMenu );
                break;
            case "config":
                return ( $blnUIsAdminApp || $profile->userProfile_pConfig );
                break;
            case "categories":
                return ( $blnUIsAdminApp || $profile->userProfile_pConfigCategories );
                break;
            case "masterpass":
                return ( $blnUIsAdminApp || $profile->userProfile_pConfigMasterPass );
                break;
            case "backup":
                return ( $blnUIsAdminApp || $profile->userProfile_pConfigBackup );
                break;
            case "usersmenu":
                return ( $blnUIsAdminApp || $profile->userProfile_pUsersMenu );
                break;
            case "users":
                return ( $blnUIsAdminApp || $profile->userProfile_pUsers );
                break;
            case "groups":
                return ( $blnUIsAdminApp || $profile->userProfile_pGroups );
                break;
            case "profiles":
                return ( $blnUIsAdminApp || $profile->userProfile_pProfiles );
                break;
            case "eventlog":
                return ( $blnUIsAdminApp || $profile->userProfile_pEventlog );
                break;
        }

        $message['action'][] = __FUNCTION__;
        $message['text'][] = _('Denegado acceso a') . " '" . $strAction . "'";

        SP_Common::wrLogInfo($message);

        return FALSE;
    }
}