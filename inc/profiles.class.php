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
 * Esta clase es la encargada de realizar las operaciones sobre los perfiles de usuarios.
 */
class SP_Profiles {

    static $profileId;
    static $profileName;
    static $queryLastId;

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
            'userProfile_pConfigMasterPass' => 0,
            'userProfile_pConfigBackup' => 0,
            'userProfile_pAppMgmtCategories' => 0,
            'userProfile_pAppMgmtCustomers' => 0,
            'userProfile_pUsers' => 0,
            'userProfile_pGroups' => 0,
            'userProfile_pProfiles' => 0,
            'userProfile_pEventlog' => 0,
            'action' => 1);

        if ($id > 0) {
            $usersProfiles = self::getProfiles($id);

            if ($usersProfiles) {
                foreach ($usersProfiles[0] as $name => $value) {
                    if (preg_match('/^.*_p[A-Z].*$/', $name)) {
                        $profile[$name] = ( (int) $value === 1 ) ? "CHECKED" : "";
                    } else {
                        $profile[$name] = $value;
                    }
                }

                $profile['action'] = 2;
            }
        }

        return $profile;
    }

    /**
     * @brief Obtener los datos de un perfil
     * @param int $profileId opcional, con el Id del perfil a consultar
     * @return array con la lista de perfiles
     */
    public static function getProfiles($profileId = NULL) {
        if (!is_null($profileId)) {
            $query = 'SELECT userprofile_id,'
                    . 'userprofile_name,'
                    . 'userProfile_pView,'
                    . 'userProfile_pViewPass,'
                    . 'userProfile_pViewHistory,'
                    . 'userProfile_pEdit,'
                    . 'userProfile_pEditPass,'
                    . 'userProfile_pAdd,'
                    . 'userProfile_pDelete,'
                    . 'userProfile_pFiles,'
                    . 'userProfile_pConfig,'
                    . 'userProfile_pConfigMasterPass,'
                    . 'userProfile_pConfigBackup,'
                    . 'userProfile_pAppMgmtCategories,'
                    . 'userProfile_pAppMgmtCustomers,'
                    . 'userProfile_pUsers,'
                    . 'userProfile_pGroups,'
                    . 'userProfile_pProfiles,'
                    . 'userProfile_pEventlog '
                    . 'FROM usrProfiles '
                    . 'WHERE userprofile_id = ' . (int) $profileId . ' LIMIT 1';
        } else {
            $query = 'SELECT userprofile_id,'
                    . 'userprofile_name '
                    . 'FROM usrProfiles '
                    . 'ORDER BY userprofile_name';
        }
        
        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes;
    }
    
    /**
     * @brief Comprobar si un perfil existe
     * @return bool
     */
    public static function checkProfileExist() {
        $profileId = (int) strtoupper(self::$profileId);
        $profileName = strtoupper(self::$profileName);

        if ($profileId) {
            $query = "SELECT userprofile_name "
                    . "FROM usrProfiles "
                    . "WHERE UPPER(userprofile_name) = '" . DB::escape($profileName) . "' "
                    . "AND userprofile_id != " . $profileId;
        } else {
            $query = "SELECT userprofile_name "
                    . "FROM usrProfiles "
                    . "WHERE UPPER(userprofile_name) = '" . DB::escape($profileName) . "'";
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
     * @brief Añadir un nuevo perfil
     * @return bool
     */
    public static function addProfile($profileProp = '') {
        $enableConfig = (int) ( $profileProp["pConfig"] || $profileProp["pConfigCat"] || $profileProp["pConfigMpw"] || $profileProp["pConfigBack"]);
        $enableAppMgmt = (int) ( $profileProp["pAppMgmt"] || $profileProp["pAppMgmtCat"] || $profileProp["pAppMgmtCust"]);
        $enableUsers = (int) ( $profileProp["pUsers"] || $profileProp["pGroups"] || $profileProp["pProfiles"]);
        
        $query = "INSERT INTO usrProfiles SET "
                . "userprofile_name = '" . DB::escape(self::$profileName) . "',"
                . "userProfile_pView = " . $profileProp["pAccView"] . ","
                . "userProfile_pViewPass = " . $profileProp["pAccViewPass"] . ","
                . "userProfile_pViewHistory = " . $profileProp["pAccViewHistory"] . ","
                . "userProfile_pEdit = " . $profileProp["pAccEdit"] . ","
                . "userProfile_pEditPass = " . $profileProp["pAccEditPass"] . ","
                . "userProfile_pAdd = " . $profileProp["pAccAdd"] . ","
                . "userProfile_pDelete = " . $profileProp["pAccDel"] . ","
                . "userProfile_pFiles = " . $profileProp["pAccFiles"] . ","
                . "userProfile_pConfigMenu = " . $enableConfig . ","
                . "userProfile_pConfig = " . $profileProp["pConfig"] . ","
                . "userProfile_pConfigMasterPass = " . $profileProp["pConfigMpw"] . ","
                . "userProfile_pConfigBackup = " . $profileProp["pConfigBack"] . ","
                . "userProfile_pAppMgmtMenu = " . $enableAppMgmt . ","
                . "userProfile_pAppMgmtCategories = " . $profileProp["pAppMgmtCat"] . ","
                . "userProfile_pAppMgmtCustomers = " . $profileProp["pAppMgmtCust"] . ","
                . "userProfile_pUsersMenu = " . $enableUsers . ","
                . "userProfile_pUsers = " . $profileProp["pUsers"] . ","
                . "userProfile_pGroups = " . $profileProp["pGroups"] . ","
                . "userProfile_pProfiles = " . $profileProp["pProfiles"] . ","
                . "userProfile_pEventlog = " . $profileProp["pEventlog"];

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        self::$queryLastId = DB::$lastId;

        return TRUE;
    }
    
    /**
     * @brief Modificar un perfil
     * @return bool
     */
    public static function updateProfile($profileProp = '') {
        $enableConfig = (int) ( $profileProp["pConfig"] || $profileProp["pConfigCat"] || $profileProp["pConfigMpw"] || $profileProp["pConfigBack"]);
        $enableAppMgmt = (int) ( $profileProp["pAppMgmt"] || $profileProp["pAppMgmtCat"] || $profileProp["pAppMgmtCust"]);
        $enableUsers = (int) ( $profileProp["pUsers"] || $profileProp["pGroups"] || $profileProp["pProfiles"]);
        
        $query = "UPDATE usrProfiles SET "
                . "userprofile_name = '" . DB::escape(self::$profileName) . "',"
                . "userProfile_pView = " . $profileProp["pAccView"] . ","
                . "userProfile_pViewPass = " . $profileProp["pAccViewPass"] . ","
                . "userProfile_pViewHistory = " . $profileProp["pAccViewHistory"] . ","
                . "userProfile_pEdit = " . $profileProp["pAccEdit"] . ","
                . "userProfile_pEditPass = " . $profileProp["pAccEditPass"] . ","
                . "userProfile_pAdd = " . $profileProp["pAccAdd"] . ","
                . "userProfile_pDelete = " . $profileProp["pAccDel"] . ","
                . "userProfile_pFiles = " . $profileProp["pAccFiles"] . ","
                . "userProfile_pConfigMenu = " . $enableConfig . ","
                . "userProfile_pConfig = " . $profileProp["pConfig"] . ","
                . "userProfile_pConfigMasterPass = " . $profileProp["pConfigMpw"] . ","
                . "userProfile_pConfigBackup = " . $profileProp["pConfigBack"] . ","
                . "userProfile_pAppMgmtMenu = " . $enableAppMgmt . ","
                . "userProfile_pAppMgmtCategories = " . $profileProp["pAppMgmtCat"] . ","
                . "userProfile_pAppMgmtCustomers = " . $profileProp["pAppMgmtCust"] . ","
                . "userProfile_pUsersMenu = " . $enableUsers . ","
                . "userProfile_pUsers = " . $profileProp["pUsers"] . ","
                . "userProfile_pGroups = " . $profileProp["pGroups"] . ","
                . "userProfile_pProfiles = " . $profileProp["pProfiles"] . ","
                . "userProfile_pEventlog = " . $profileProp["pEventlog"] . " "
                . "WHERE userprofile_id = " . (int) self::$profileId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        self::$queryLastId = DB::$lastId;

        return TRUE;
    }

    /**
     * @brief Eliminar un perfil
     * @return bool
     */
    public static function deleteProfile() {
        $query = "DELETE FROM usrProfiles "
                . "WHERE userprofile_id = " . (int) self::$profileId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        self::$queryLastId = DB::$lastId;

        return TRUE;
    }
    
    /**
     * @brief Comprobar si un perfil está en uso
     * @return mixed string con el número de usuarios, o bool si no está en uso
     */
    public static function checkProfileInUse() {
        $count['users'] = self::getProfileInUsers();
        return $count;
    }
    
    /**
     * @brief Obtener el número de usuarios que usan un perfil
     * @return int con el número total de cuentas
     */
    private static function getProfileInUsers() {
        $query = "SELECT COUNT(*) as uses "
                . "FROM usrData "
                . "WHERE user_profileId = " . (int) self::$profileId;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->uses;
    }
    
    /**
     * @brief Obtener el nombre de un perfil por a partir del Id
     * @return string con el nombre del perfil
     */
    public static function getProfileNameById($id) {
        $query = "SELECT userprofile_name "
                . "FROM usrProfiles "
                . "WHERE userprofile_id = " . (int)$id . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes->userprofile_name;
    }
    
   /**
    * @brief Obtener el perfil de un usuario
    * @param int $userId opcional con el Id del usuario
    * @return object con los permisos del perfil del usuario
    * 
    * Si el usuario no es indicado, se obtiene el perfil del suuario de la sesión actual 
    */
    public static function getProfileForUser($userId = 0) {
        $userId = SP_Common::parseParams('s', 'uid', 0);
        
        if ( ! $userId ){
            return FALSE;
        }
        
        $query = "SELECT user_profileId,"
                . "userProfile_pView,"
                . "userProfile_pViewPass,"
                . "userProfile_pViewHistory,"
                . "userProfile_pEdit,"
                . "userProfile_pEditPass,"
                . "userProfile_pAdd,"
                . "userProfile_pDelete,"
                . "userProfile_pFiles,"
                . "userProfile_pConfigMenu,"
                . "userProfile_pConfig,"
                . "userProfile_pConfigMasterPass,"
                . "userProfile_pConfigBackup,"
                . "userProfile_pAppMgmtMenu,"                
                . 'userProfile_pAppMgmtCategories,'
                . 'userProfile_pAppMgmtCustomers,'
                . "userProfile_pUsersMenu,"
                . "userProfile_pUsers,"
                . "userProfile_pGroups,"
                . "userProfile_pProfiles,"
                . "userProfile_pEventlog "
                . "FROM usrData "
                . "JOIN usrProfiles ON userProfile_Id = user_profileId "
                . "WHERE user_id = " . $userId . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        return $queryRes;
    }

}
