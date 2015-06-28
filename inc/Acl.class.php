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
 * Esta clase es la encargada de calcular las access lists de acceso a usuarios.
 */
class Acl implements Controller\ActionsInterface
{
    /**
     * Comprobar los permisos de acceso del usuario a los módulos de la aplicación.
     * Esta función comprueba los permisos del usuario para realizar una acción.
     * Si los permisos ya han sido obtenidos desde la BBDD, se utiliza el objeto creado
     * en la variable de sesión.
     *
     * @param string $action con el nombre de la acción
     * @param int    $userId opcional, con el Id del usuario
     * @return bool
     */
    public static function checkUserAccess($action, $userId = 0)
    {
        // Comprobamos si la cache de permisos está inicializada
        if (!is_object(Session::getUserProfile())) {
//            error_log('ACL_CACHE_MISS');
            return false;
        }

        $curUserIsAdminApp = Session::getUserIsAdminApp();
        $curUserIsAdminAcc = Session::getUserIsAdminAcc();
        $curUserProfile = Session::getUserProfile();
        $curUserId = Session::getUserId();

        switch ($action) {
            case self::ACTION_ACC_VIEW:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccView() || $curUserProfile->isAccEdit());
            case self::ACTION_ACC_VIEW_PASS:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccViewPass());
            case self::ACTION_ACC_VIEW_HISTORY:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccViewHistory());
            case self::ACTION_ACC_EDIT:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccEdit());
            case self::ACTION_ACC_EDIT_PASS:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccEditPass());
            case self::ACTION_ACC_NEW:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccAdd());
            case self::ACTION_ACC_COPY:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || ($curUserProfile->isAccAdd() && $curUserProfile->isAccView()));
            case self::ACTION_ACC_DELETE:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccDelete());
            case self::ACTION_ACC_FILES:
                return ($curUserIsAdminApp || $curUserIsAdminAcc || $curUserProfile->isAccFiles());
            case self::ACTION_MGM:
                return ($curUserIsAdminApp || $curUserProfile->isMgmCategories() || $curUserProfile->isMgmCustomers());
            case self::ACTION_CFG:
                return ($curUserIsAdminApp || $curUserProfile->isConfigGeneral() || $curUserProfile->isConfigEncryption() || $curUserProfile->isConfigBackup() || $curUserProfile->isConfigImport());
            case self::ACTION_CFG_GENERAL:
                return ($curUserIsAdminApp || $curUserProfile->isConfigGeneral());
            case self::ACTION_CFG_IMPORT:
                return ($curUserIsAdminApp || $curUserProfile->isConfigBackup());
            case self::ACTION_MGM_CATEGORIES:
                return ($curUserIsAdminApp || $curUserProfile->isMgmCategories());
            case self::ACTION_MGM_CUSTOMERS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmCustomers());
            case self::ACTION_CFG_ENCRYPTION:
                return ($curUserIsAdminApp || $curUserProfile->isConfigEncryption());
            case self::ACTION_CFG_BACKUP:
                return ($curUserIsAdminApp || $curUserProfile->isConfigBackup());
            case self::ACTION_USR:
                return ($curUserIsAdminApp || $curUserProfile->isMgmUsers() || $curUserProfile->isMgmGroups() || $curUserProfile->isMgmProfiles());
            case self::ACTION_USR_USERS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmUsers());
            case self::ACTION_USR_USERS_EDITPASS:
                return ($userId == $curUserId || $curUserIsAdminApp || $curUserProfile->isMgmUsers());
            case self::ACTION_USR_GROUPS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmGroups());
            case self::ACTION_USR_PROFILES:
                return ($curUserIsAdminApp || $curUserProfile->isMgmProfiles());
            case self::ACTION_EVL:
                return ($curUserIsAdminApp || $curUserProfile->isEvl());
        }

        Log::writeNewLog(__FUNCTION__, _('Denegado acceso a') . " '" . self::getActionName($action) . "'");

        return false;
    }

    /**
     * Obtener el nombre de la acción indicada
     *
     * @param int $action El id de la acción
     * @return string
     */
    public static function getActionName($action)
    {
        $actionName = array(
            self::ACTION_ACC_SEARCH => 'acc_search',
            self::ACTION_ACC_VIEW => 'acc_view',
            self::ACTION_ACC_COPY => 'acc_copy',
            self::ACTION_ACC_NEW => 'acc_new',
            self::ACTION_ACC_EDIT => 'acc_edit',
            self::ACTION_ACC_EDIT_PASS => 'acc_editpass',
            self::ACTION_ACC_VIEW_HISTORY => 'acc_viewhist',
            self::ACTION_ACC_VIEW_PASS => 'acc_viewpass',
            self::ACTION_ACC_DELETE => 'acc_delete',
            self::ACTION_ACC_FILES => 'acc_files',
            self::ACTION_ACC_REQUEST => 'acc_request',
            self::ACTION_MGM => 'mgm',
            self::ACTION_MGM_CATEGORIES => 'mgm_categories',
            self::ACTION_MGM_CUSTOMERS => 'mgm_customers',
            self::ACTION_USR => 'usr',
            self::ACTION_USR_USERS => 'usr_users',
            self::ACTION_USR_GROUPS => 'usr_groups',
            self::ACTION_USR_PROFILES => 'usr_profiles',
            self::ACTION_CFG => 'cfg',
            self::ACTION_CFG_GENERAL => 'cfg_general',
            self::ACTION_CFG_ENCRYPTION => 'cfg_encryption',
            self::ACTION_CFG_BACKUP => 'cfg_backup',
            self::ACTION_CFG_IMPORT => 'cfg_import',
            self::ACTION_EVL => 'evl'
        );

        if (!isset($actionName[$action])) {
            return 'action';
        }

        return $actionName[$action];
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param string $module      con la acción realizada
     * @param array  $accountData con los datos de la cuenta a verificar
     * @return bool
     */
    public static function checkAccountAccess($module, $accountData)
    {
        $userGroupId = Session::getUserGroupId();
        $userId = Session::getUserId();
        $userIsAdminApp = Session::getUserIsAdminApp();
        $userIsAdminAcc = Session::getUserIsAdminAcc();

        $okView = ($userId == $accountData['user_id']
            || $userGroupId == $accountData['group_id']
            || in_array($userId, $accountData['users_id'])
            || in_array($userGroupId, $accountData['groups_id'])
            || $userIsAdminApp
            || $userIsAdminAcc);

        $okEdit = ($userId == $accountData['user_id']
            || $userGroupId == $accountData['group_id']
            || (in_array($userId, $accountData['users_id']) && $accountData['otheruser_edit'])
            || (in_array($userGroupId, $accountData['groups_id']) && $accountData['othergroup_edit'])
            || $userIsAdminApp
            || $userIsAdminAcc);

        switch ($module) {
            case self::ACTION_ACC_VIEW:
                return $okView;
            case self::ACTION_ACC_VIEW_PASS:
                return $okView;
            case self::ACTION_ACC_VIEW_HISTORY:
                return $okView;
            case self::ACTION_ACC_EDIT:
                return $okEdit;
            case self::ACTION_ACC_DELETE:
                return $okEdit;
            case self::ACTION_ACC_EDIT_PASS:
                return $okEdit;
            case self::ACTION_ACC_COPY:
                return $okView;
        }

        return false;
    }
}