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
 * Esta clase es la encargada de calcular las access lists de acceso a usuarios.
 */
class SP_ACL
{

    static $accountCacheUserGroupsId;

    /**
     * Comprobar los permisos de acceso del usuario a los módulos de la aplicación.
     * Esta función comprueba los permisos del usuario para realizar una acción.
     * Si los permisos ya han sido obtenidos desde la BBDD, se utiliza el objeto creado
     * en la variable de sesión.
     *
     * @param string $strAction con el nombre de la acción
     * @param int $userId opcional, con el Id del usuario
     * @return bool
     */
    public static function checkUserAccess($strAction, $userId = 0)
    {
        // Comprobamos si la cache de permisos está inicializada
        if (!isset($_SESSION["usrprofile"]) || !is_object($_SESSION["usrprofile"])) {
            return false;
        }

        $blnUIsAdminApp = $_SESSION["uisadminapp"];
        $blnUIsAdminAcc = $_SESSION["uisadminacc"];
        $profile = $_SESSION["usrprofile"];

        switch ($strAction) {
            case "accview":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pView);
            case "accviewpass":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pViewPass);
            case "accviewhistory":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pViewHistory);
            case "accedit":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pEdit);
            case "acceditpass":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pEditPass || $userId == $_SESSION["uid"]);
            case "accnew":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pAdd);
            case "acccopy":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || ($profile->userProfile_pAdd && $profile->userProfile_pView));
            case "accdelete":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pDelete);
            case "accfiles":
                return ($blnUIsAdminApp || $blnUIsAdminAcc || $profile->userProfile_pFiles);
            case "appmgmtmenu":
                return ($blnUIsAdminApp || $profile->userProfile_pAppMgmtMenu);
            case "configmenu":
                return ($blnUIsAdminApp || $profile->userProfile_pConfigMenu);
            case "config":
                return ($blnUIsAdminApp || $profile->userProfile_pConfig);
            case "categories":
                return ($blnUIsAdminApp || $profile->userProfile_pAppMgmtCategories);
            case "customers":
                return ($blnUIsAdminApp || $profile->userProfile_pAppMgmtCustomers);
            case "masterpass":
                return ($blnUIsAdminApp || $profile->userProfile_pConfigMasterPass);
            case "backup":
                return ($blnUIsAdminApp || $profile->userProfile_pConfigBackup);
            case "usersmenu":
                return ($blnUIsAdminApp || $profile->userProfile_pUsersMenu);
            case "users":
                return ($blnUIsAdminApp || $profile->userProfile_pUsers);
            case "groups":
                return ($blnUIsAdminApp || $profile->userProfile_pGroups);
            case "profiles":
                return ($blnUIsAdminApp || $profile->userProfile_pProfiles);
            case "eventlog":
                return ($blnUIsAdminApp || $profile->userProfile_pEventlog);
        }

        $message['action'][] = __FUNCTION__;
        $message['text'][] = _('Denegado acceso a') . " '" . $strAction . "'";

        SP_Log::wrLogInfo($message);

        return false;
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param string $action con la acción realizada
     * @param array $accountData con los datos de la cuenta a verificar
     * @return bool
     */
    public static function checkAccountAccess($action, $accountData)
    {
        $userGroupId = $_SESSION["ugroup"];
        $userId = $_SESSION["uid"];
        $userIsAdminApp = $_SESSION["uisadminapp"];
        $userIsAdminAcc = $_SESSION["uisadminacc"];

        switch ($action) {
            case "accview":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || in_array($userId, $accountData['users_id'])
                    || in_array($userGroupId, $accountData['groups_id'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
            case "accviewpass":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || in_array($userId, $accountData['users_id'])
                    || in_array($userGroupId, $accountData['groups_id'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
            case "accviewhistory":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || in_array($userId, $accountData['users_id'])
                    || in_array($userGroupId, $accountData['groups_id'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
            case "accedit":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || (in_array($userId, $accountData['users_id']) && $accountData['otheruser_edit'])
                    || (in_array($userGroupId, $accountData['groups_id']) && $accountData['othergroup_edit'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
            case "accdelete":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || (in_array($userId, $accountData['users_id']) && $accountData['otheruser_edit'])
                    || (in_array($userGroupId, $accountData['groups_id']) && $accountData['othergroup_edit'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
            case "acceditpass":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || (in_array($userId, $accountData['users_id']) && $accountData['otheruser_edit'])
                    || (in_array($userGroupId, $accountData['groups_id']) && $accountData['othergroup_edit'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
            case "acccopy":
                return ($userId == $accountData['user_id']
                    || $userGroupId == $accountData['group_id']
                    || in_array($userId, $accountData['users_id'])
                    || in_array($userGroupId, $accountData['groups_id'])
                    || $userIsAdminApp
                    || $userIsAdminAcc);
        }

        return false;
    }
}