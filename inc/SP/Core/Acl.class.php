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

namespace SP\Core;

use SP\DataModel\AccountData;
use SP\Controller;
use SP\Mgmt\Groups\Groups;
use SP\Log\Log;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de calcular las access lists de acceso a usuarios.
 */
class Acl implements ActionsInterface
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
                return ($curUserIsAdminApp || $curUserProfile->isConfigImport());
            case self::ACTION_MGM_CATEGORIES:
                return ($curUserIsAdminApp || $curUserProfile->isMgmCategories());
            case self::ACTION_MGM_CUSTOMERS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmCustomers());
            case self::ACTION_MGM_CUSTOMFIELDS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmCustomFields());
            case self::ACTION_MGM_PUBLICLINKS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmPublicLinks());
            case self::ACTION_MGM_PUBLICLINKS_NEW:
                return ($curUserIsAdminApp || $curUserProfile->isMgmPublicLinks() || $curUserProfile->isAccPublicLinks());
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
            case self::ACTION_MGM_APITOKENS:
                return ($curUserIsAdminApp || $curUserProfile->isMgmApiTokens());
            case self::ACTION_EVL:
                return ($curUserIsAdminApp || $curUserProfile->isEvl());
        }

        Log::writeNewLog(__FUNCTION__, sprintf('%s \'%s\'', _('Denegado acceso a'), self::getActionName($action)), Log::NOTICE);

        return false;
    }

    /**
     * Obtener el nombre de la acción indicada
     *
     * @param int  $action El id de la acción
     * @param bool $shortName Si se devuelve el nombre corto de la acción
     * @return string
     */
    public static function getActionName($action, $shortName = false)
    {
        $actionName = array(
            self::ACTION_ACC_SEARCH => array('acc_search', _('Buscar Cuentas')),
            self::ACTION_ACC_VIEW => array('acc_view', _('Ver Cuenta')),
            self::ACTION_ACC_COPY => array('acc_copy', _('Copiar Cuenta')),
            self::ACTION_ACC_NEW => array('acc_new', _('Nueva Cuenta')),
            self::ACTION_ACC_EDIT => array('acc_edit', _('Editar Cuenta')),
            self::ACTION_ACC_EDIT_PASS => array('acc_editpass', _('Editar Clave de Cuenta')),
            self::ACTION_ACC_VIEW_HISTORY => array('acc_viewhist', _('Ver Historial')),
            self::ACTION_ACC_VIEW_PASS => array('acc_viewpass', _('Ver Clave')),
            self::ACTION_ACC_DELETE => array('acc_delete', _('Eliminar Cuenta')),
            self::ACTION_ACC_FILES => array('acc_files', _('Archivos')),
            self::ACTION_ACC_REQUEST => array('acc_request', _('Peticiones')),
            self::ACTION_MGM => array('mgm', _('Gestión Aplicación')),
            self::ACTION_MGM_CATEGORIES => array('mgm_categories', _('Gestión Categorías')),
            self::ACTION_MGM_CUSTOMERS => array('mgm_customers', _('Gestión Clientes')),
            self::ACTION_MGM_CUSTOMFIELDS => array('mgm_customfields', _('Gestión Campos Personalizados')),
            self::ACTION_MGM_APITOKENS => array('mgm_apitokens', _('Gestión Autorizaciones API')),
            self::ACTION_USR => array('usr', _('Gestión Usuarios')),
            self::ACTION_USR_USERS => array('usr_users', _('Gestión Usuarios')),
            self::ACTION_USR_GROUPS => array('usr_groups', _('Gestión Grupos')),
            self::ACTION_USR_PROFILES => array('usr_profiles', _('Gestión Perfiles')),
            self::ACTION_CFG => array('cfg', _('Configuración')),
            self::ACTION_CFG_GENERAL => array('cfg_general', _('Configuración General')),
            self::ACTION_CFG_ENCRYPTION => array('cfg_encryption', _('Encriptación')),
            self::ACTION_CFG_BACKUP => array('cfg_backup', _('Copia de Seguridad')),
            self::ACTION_CFG_EXPORT => array('cfg_export', _('Exportar')),
            self::ACTION_CFG_IMPORT => array('cfg_import', _('Importar')),
            self::ACTION_EVL => 'evl'
        );

        if (!isset($actionName[$action])) {
            return 'action';
        }

        if ($shortName) {
            return $actionName[$action][0];
        }

        return $actionName[$action][1];
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param string      $module      con la acción realizada
     * @param AccountData $accountData con los datos de la cuenta a verificar
     * @return bool
     */
    public static function checkAccountAccess($module, AccountData $accountData)
    {
        $userGroupId = Session::getUserGroupId();
        $userId = Session::getUserId();
        $userIsAdminApp = Session::getUserIsAdminApp();
        $userIsAdminAcc = Session::getUserIsAdminAcc();
        $userToGroups = in_array($userGroupId, Groups::getUsersForGroup($accountData->getAccountUserGroupId()));

        if ($userToGroups === false) {
            foreach ($accountData->getAccountUserGroupsId() as $groupId) {
                $users = Groups::getUsersForGroup($groupId);
                if ($userGroupId === $groupId || in_array($userId, $users)) {
                    $userToGroups = true;
                }
            }
        }

        $okView = ($userId == $accountData->getAccountUserId()
            || $userGroupId == $accountData->getAccountUserGroupId()
            || in_array($userId, $accountData->getAccountUsersId())
            || $userToGroups
            || $userIsAdminApp
            || $userIsAdminAcc);

        $okEdit = ($userId == $accountData->getAccountUserId()
            || $userGroupId == $accountData->getAccountUserGroupId()
            || (in_array($userId, $accountData->getAccountUsersId()) && $accountData->getAccountOtherUserEdit())
            || ($userToGroups && $accountData->getAccountOtherGroupEdit())
            || $userIsAdminApp
            || $userIsAdminAcc);

        switch ($module) {
            case self::ACTION_ACC_VIEW:
            case self::ACTION_ACC_VIEW_PASS:
            case self::ACTION_ACC_VIEW_HISTORY:
            case self::ACTION_ACC_COPY:
                return $okView;
            case self::ACTION_ACC_EDIT:
            case self::ACTION_ACC_DELETE:
            case self::ACTION_ACC_EDIT_PASS:
                return $okEdit;
        }

        return false;
    }
}