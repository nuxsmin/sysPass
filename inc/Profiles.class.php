<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre los perfiles de usuarios.
 */
class SP_Profiles
{
    static $profileId;
    static $profileName;
    static $queryLastId;

    /**
     * Obtener los datos de un perfil
     * @param int $id con el Id del perfil a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getProfileData($id = 0)
    {

        $profile = array('id' => 0,
            'name' => '',
            'pView' => 0,
            'pViewPass' => 0,
            'pViewHistory' => 0,
            'pEdit' => 0,
            'pEditPass' => 0,
            'pAdd' => 0,
            'pDelete' => 0,
            'pFiles' => 0,
            'pConfig' => 0,
            'pConfigMasterPass' => 0,
            'pConfigBackup' => 0,
            'pAppMgmtCategories' => 0,
            'pAppMgmtCustomers' => 0,
            'pUsers' => 0,
            'pGroups' => 0,
            'pProfiles' => 0,
            'pEventlog' => 0,
            'action' => 1);

        if ($id > 0) {
            $usersProfiles = self::getProfiles($id);

            if ($usersProfiles) {
                foreach ($usersProfiles[0] as $name => $value) {
                    if (preg_match('/^p[A-Za-z].*$/', $name)) {
                        $profile[$name] = (intval($value) === 1) ? "CHECKED" : "";
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
     * Obtener los datos de un perfil
     * @param int $profileId opcional, con el Id del perfil a consultar
     * @return array con la lista de perfiles
     */
    public static function getProfiles($profileId = null)
    {
        $data = null;

        if (!is_null($profileId)) {
            $query = 'SELECT userprofile_id AS id,'
                . 'userprofile_name AS name,'
                . 'BIN(userProfile_pView) AS pView,'
                . 'BIN(userProfile_pViewPass) AS pViewPass,'
                . 'BIN(userProfile_pViewHistory) AS pViewHistory,'
                . 'BIN(userProfile_pEdit) AS pEdit,'
                . 'BIN(userProfile_pEditPass) AS pEditPass,'
                . 'BIN(userProfile_pAdd) AS pAdd,'
                . 'BIN(userProfile_pDelete) AS pDelete,'
                . 'BIN(userProfile_pFiles) AS pFiles,'
                . 'BIN(userProfile_pConfig) AS pConfig,'
                . 'BIN(userProfile_pConfigMasterPass) AS pConfigMasterPass,'
                . 'BIN(userProfile_pConfigBackup) AS pConfigBackup,'
                . 'BIN(userProfile_pAppMgmtCategories) AS pAppMgmtCategories,'
                . 'BIN(userProfile_pAppMgmtCustomers) AS pAppMgmtCustomers,'
                . 'BIN(userProfile_pUsers) AS pUsers,'
                . 'BIN(userProfile_pGroups) AS pGroups,'
                . 'BIN(userProfile_pProfiles) AS pProfiles,'
                . 'BIN(userProfile_pEventlog) AS pEventlog '
                . 'FROM usrProfiles '
                . 'WHERE userprofile_id = :id LIMIT 1';

            $data['id'] = $profileId;
        } else {
            $query = 'SELECT userprofile_id,'
                . 'userprofile_name '
                . 'FROM usrProfiles '
                . 'ORDER BY userprofile_name';
        }

        DB::setReturnArray();

        return DB::getResults($query, __FUNCTION__, $data);
    }

    /**
     * Comprobar si un perfil existe
     * @return bool
     */
    public static function checkProfileExist()
    {
        $profileId = (int)strtoupper(self::$profileId);
        $profileName = strtoupper(self::$profileName);

        if ($profileId) {
            $query = 'SELECT userprofile_name '
                . 'FROM usrProfiles '
                . 'WHERE UPPER(userprofile_name) = :name '
                . 'AND userprofile_id != :id';

            $data['id'] = $profileId;
        } else {
            $query = 'SELECT userprofile_name '
                . 'FROM usrProfiles '
                . 'WHERE UPPER(userprofile_name) = :name';
        }

        $data['name'] = $profileName;

        return (DB::getQuery($query, __FUNCTION__, $data) === true && DB::$last_num_rows >= 1);
    }

    /**
     * Añadir un nuevo perfil
     * @param array $profileProp con las propiedades del perfil
     * @return bool
     */
    public static function addProfile(&$profileProp)
    {
        $enableConfig = (int)($profileProp["pConfig"] || $profileProp["pConfigMpw"] || $profileProp["pConfigBack"]);
        $enableAppMgmt = (int)($profileProp["pAppMgmt"] || $profileProp["pAppMgmtCat"] || $profileProp["pAppMgmtCust"]);
        $enableUsers = (int)($profileProp["pUsers"] || $profileProp["pGroups"] || $profileProp["pProfiles"]);

        $query = 'INSERT INTO usrProfiles SET '
            . 'userprofile_name = :name,'
            . 'userProfile_pView = :pView,'
            . 'userProfile_pViewPass = :pViewPass,'
            . 'userProfile_pViewHistory = :pViewHistory,'
            . 'userProfile_pEdit = :pEdit,'
            . 'userProfile_pEditPass = :pEditPass,'
            . 'userProfile_pAdd = :pAdd,'
            . 'userProfile_pDelete = :pDelete,'
            . 'userProfile_pFiles = :pFiles,'
            . 'userProfile_pConfigMenu = :pConfigMenu,'
            . 'userProfile_pConfig = :pConfig,'
            . 'userProfile_pConfigMasterPass = :pConfigMasterPass,'
            . 'userProfile_pConfigBackup = :pConfigBackup,'
            . 'userProfile_pAppMgmtMenu = :pAppMgmtMenu,'
            . 'userProfile_pAppMgmtCategories = :pAppMgmtCategories,'
            . 'userProfile_pAppMgmtCustomers = :pAppMgmtCustomers,'
            . 'userProfile_pUsersMenu = :pUsersMenu,'
            . 'userProfile_pUsers = :pUsers,'
            . 'userProfile_pGroups = :pGroups,'
            . 'userProfile_pProfiles = :pProfiles,'
            . 'userProfile_pEventlog = :pEventlog';

        $data['name'] = self::$profileName;
        $data['pView'] = $profileProp["pAccView"];
        $data['pViewPass'] = $profileProp["pAccViewPass"];
        $data['pViewHistory'] = $profileProp["pAccViewHistory"];
        $data['pEdit'] = $profileProp["pAccEdit"];
        $data['pEditPass'] = $profileProp["pAccEditPass"];
        $data['pAdd'] = $profileProp["pAccAdd"];
        $data['pDelete'] = $profileProp["pAccDel"];
        $data['pFiles'] = $profileProp["pAccFiles"];
        $data['pConfigMenu'] = $enableConfig;
        $data['pConfig'] = $profileProp["pConfig"];
        $data['pConfigMasterPass'] = $profileProp["pConfigMpw"];
        $data['pConfigBackup'] = $profileProp["pConfigBack"];
        $data['pAppMgmtMenu'] = $enableAppMgmt;
        $data['pAppMgmtCategories'] = $profileProp["pAppMgmtCat"];
        $data['pAppMgmtCustomers'] = $profileProp["pAppMgmtCust"];
        $data['pUsersMenu'] = $enableUsers;
        $data['pUsers'] = $profileProp["pUsers"];
        $data['pGroups'] = $profileProp["pGroups"];
        $data['pProfiles'] = $profileProp["pProfiles"];
        $data['pEventlog'] = $profileProp["pEventlog"];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Nuevo Perfil');
        $message['text'][] = SP_Html::strongText(_('Perfil') . ': ') . self::$profileName;

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Modificar un perfil.
     *
     * @param array $profileProp con las propiedades del perfil
     * @return bool
     */
    public static function updateProfile(&$profileProp)
    {
        $enableConfig = (int)($profileProp["pConfig"] || $profileProp["pConfigMpw"] || $profileProp["pConfigBack"]);
        $enableAppMgmt = (int)($profileProp["pAppMgmtCat"] || $profileProp["pAppMgmtCust"]);
        $enableUsers = (int)($profileProp["pUsers"] || $profileProp["pGroups"] || $profileProp["pProfiles"]);
        $profileName = self::getProfileNameById(self::$profileId);

        $query = 'UPDATE usrProfiles SET '
            . 'userprofile_name = :name,'
            . 'userProfile_pView = :pView,'
            . 'userProfile_pViewPass = :pViewPass,'
            . 'userProfile_pViewHistory = :pViewHistory,'
            . 'userProfile_pEdit = :pEdit,'
            . 'userProfile_pEditPass = :pEditPass,'
            . 'userProfile_pAdd = :pAdd,'
            . 'userProfile_pDelete = :pDelete,'
            . 'userProfile_pFiles = :pFiles,'
            . 'userProfile_pConfigMenu = :pConfigMenu,'
            . 'userProfile_pConfig = :pConfig,'
            . 'userProfile_pConfigMasterPass = :pConfigMasterPass,'
            . 'userProfile_pConfigBackup = :pConfigBackup,'
            . 'userProfile_pAppMgmtMenu = :pAppMgmtMenu,'
            . 'userProfile_pAppMgmtCategories = :pAppMgmtCategories,'
            . 'userProfile_pAppMgmtCustomers = :pAppMgmtCustomers,'
            . 'userProfile_pUsersMenu = :pUsersMenu,'
            . 'userProfile_pUsers = :pUsers,'
            . 'userProfile_pGroups = :pGroups,'
            . 'userProfile_pProfiles = :pProfiles,'
            . 'userProfile_pEventlog = :pEventlog '
            . 'WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = self::$profileId;
        $data['name'] = self::$profileName;
        $data['pView'] = $profileProp["pAccView"];
        $data['pViewPass'] = $profileProp["pAccViewPass"];
        $data['pViewHistory'] = $profileProp["pAccViewHistory"];
        $data['pEdit'] = $profileProp["pAccEdit"];
        $data['pEditPass'] = $profileProp["pAccEditPass"];
        $data['pAdd'] = $profileProp["pAccAdd"];
        $data['pDelete'] = $profileProp["pAccDel"];
        $data['pFiles'] = $profileProp["pAccFiles"];
        $data['pConfigMenu'] = $enableConfig;
        $data['pConfig'] = $profileProp["pConfig"];
        $data['pConfigMasterPass'] = $profileProp["pConfigMpw"];
        $data['pConfigBackup'] = $profileProp["pConfigBack"];
        $data['pAppMgmtMenu'] = $enableAppMgmt;
        $data['pAppMgmtCategories'] = $profileProp["pAppMgmtCat"];
        $data['pAppMgmtCustomers'] = $profileProp["pAppMgmtCust"];
        $data['pUsersMenu'] = $enableUsers;
        $data['pUsers'] = $profileProp["pUsers"];
        $data['pGroups'] = $profileProp["pGroups"];
        $data['pProfiles'] = $profileProp["pProfiles"];
        $data['pEventlog'] = $profileProp["pEventlog"];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Modificar Perfil');
        $message['text'][] = SP_Html::strongText(_('Perfil') . ': ') . $profileName . ' > ' . self::$profileName;

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Eliminar un perfil.
     *
     * @return bool
     */
    public static function deleteProfile()
    {
        $query = 'DELETE FROM usrProfiles WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = self::$profileId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        return true;
    }

    /**
     * Comprobar si un perfil está en uso.
     *
     * @return bool|int Cadena con el número de usuarios, o bool si no está en uso
     */
    public static function checkProfileInUse()
    {
        $count['users'] = self::getProfileInUsers();
        return $count;
    }

    /**
     * Obtener el número de usuarios que usan un perfil.
     *
     * @return false|int con el número total de cuentas
     */
    private static function getProfileInUsers()
    {
        $query = 'SELECT user_profileId FROM usrData WHERE user_profileId = :id';

        $data['id'] = self::$profileId;

        DB::getQuery($query, __FUNCTION__, $data);

        return DB::$last_num_rows;
    }

    /**
     * Obtener el nombre de un perfil por a partir del Id.
     *
     * @param int $id con el Id del perfil
     * @return false|string con el nombre del perfil
     */
    public static function getProfileNameById($id)
    {
        $query = 'SELECT userprofile_name FROM usrProfiles WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->userprofile_name;
    }

    /**
     * Obtener el perfil de un usuario.
     * Si el usuario no es indicado, se obtiene el perfil del suuario de la sesión actual
     *
     * @param int $userId opcional con el Id del usuario
     * @return false|object con los permisos del perfil del usuario
     */
    public static function getProfileForUser($userId = 0)
    {
        $userId = SP_Session::getUserId();

        if (!$userId) {
            return false;
        }

        $query = 'SELECT user_profileId,'
            . 'BIN(userProfile_pView) AS pView,'
            . 'BIN(userProfile_pViewPass) AS pViewPass,'
            . 'BIN(userProfile_pViewHistory) AS pViewHistory,'
            . 'BIN(userProfile_pEdit) AS pEdit,'
            . 'BIN(userProfile_pEditPass) AS pEditPass,'
            . 'BIN(userProfile_pAdd) AS pAdd,'
            . 'BIN(userProfile_pDelete) AS pDelete,'
            . 'BIN(userProfile_pFiles) AS pFiles,'
            . 'BIN(userProfile_pConfig) AS pConfig,'
            . 'BIN(userProfile_pConfigMasterPass) AS pConfigMasterPass,'
            . 'BIN(userProfile_pConfigBackup) AS pConfigBackup,'
            . 'BIN(userProfile_pAppMgmtCategories) AS pAppMgmtCategories,'
            . 'BIN(userProfile_pAppMgmtCustomers) AS pAppMgmtCustomers,'
            . 'BIN(userProfile_pUsers) AS pUsers,'
            . 'BIN(userProfile_pGroups) AS pGroups,'
            . 'BIN(userProfile_pProfiles) AS pProfiles,'
            . 'BIN(userProfile_pEventlog) AS pEventlog '
            . 'FROM usrData '
            . 'JOIN usrProfiles ON userProfile_Id = user_profileId '
            . 'WHERE user_id = :id LIMIT 1';

        $data['id'] = $userId;

        return DB::getResults($query, __FUNCTION__, $data);
    }
}
