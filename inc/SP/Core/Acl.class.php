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
use SP\DataModel\AccountExtData;
use SP\Mgmt\Groups\Group;
use SP\Log\Log;
use SP\Mgmt\Groups\GroupUsers;

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

        $curUserIsAdminApp = Session::getUserData()->isUserIsAdminApp();
        $curUserIsAdminAcc = Session::getUserData()->isUserIsAdminAcc();
        $curUserProfile = Session::getUserProfile();
        $curUserId = Session::getUserData()->getUserId();

        if ($curUserIsAdminApp) {
            return true;
        }

        switch ($action) {
            case self::ACTION_ACC_VIEW:
                return ($curUserIsAdminAcc || $curUserProfile->isAccView() || $curUserProfile->isAccEdit());
            case self::ACTION_ACC_VIEW_PASS:
                return ($curUserIsAdminAcc || $curUserProfile->isAccViewPass());
            case self::ACTION_ACC_VIEW_HISTORY:
                return ($curUserIsAdminAcc || $curUserProfile->isAccViewHistory());
            case self::ACTION_ACC_EDIT:
                return ($curUserIsAdminAcc || $curUserProfile->isAccEdit());
            case self::ACTION_ACC_EDIT_PASS:
                return ($curUserIsAdminAcc || $curUserProfile->isAccEditPass());
            case self::ACTION_ACC_NEW:
                return ($curUserIsAdminAcc || $curUserProfile->isAccAdd());
            case self::ACTION_ACC_COPY:
                return ($curUserIsAdminAcc || ($curUserProfile->isAccAdd() && $curUserProfile->isAccView()));
            case self::ACTION_ACC_DELETE:
                return ($curUserIsAdminAcc || $curUserProfile->isAccDelete());
            case self::ACTION_ACC_FILES:
                return ($curUserIsAdminAcc || $curUserProfile->isAccFiles());
            case self::ACTION_MGM:
                return ($curUserProfile->isMgmCategories() || $curUserProfile->isMgmCustomers());
            case self::ACTION_CFG:
                return ($curUserProfile->isConfigGeneral() || $curUserProfile->isConfigEncryption() || $curUserProfile->isConfigBackup() || $curUserProfile->isConfigImport());
            case self::ACTION_CFG_GENERAL:
                return $curUserProfile->isConfigGeneral();
            case self::ACTION_CFG_IMPORT:
                return $curUserProfile->isConfigImport();
            case self::ACTION_MGM_CATEGORIES:
                return $curUserProfile->isMgmCategories();
            case self::ACTION_MGM_CUSTOMERS:
                return $curUserProfile->isMgmCustomers();
            case self::ACTION_MGM_CUSTOMFIELDS:
                return $curUserProfile->isMgmCustomFields();
            case self::ACTION_MGM_PUBLICLINKS:
                return $curUserProfile->isMgmPublicLinks();
            case self::ACTION_MGM_PUBLICLINKS_NEW:
                return ($curUserProfile->isMgmPublicLinks() || $curUserProfile->isAccPublicLinks());
            case self::ACTION_MGM_ACCOUNTS:
                return $curUserProfile->isMgmAccounts();
            case self::ACTION_MGM_FILES:
                return $curUserProfile->isMgmFiles();
            case self::ACTION_MGM_TAGS:
                return $curUserProfile->isMgmTags();
            case self::ACTION_CFG_ENCRYPTION:
                return $curUserProfile->isConfigEncryption();
            case self::ACTION_CFG_BACKUP:
                return $curUserProfile->isConfigBackup();
            case self::ACTION_USR:
                return ($curUserProfile->isMgmUsers() || $curUserProfile->isMgmGroups() || $curUserProfile->isMgmProfiles());
            case self::ACTION_USR_USERS:
                return $curUserProfile->isMgmUsers();
            case self::ACTION_USR_USERS_EDITPASS:
                return ($userId === $curUserId || $curUserProfile->isMgmUsers());
            case self::ACTION_USR_GROUPS:
                return $curUserProfile->isMgmGroups();
            case self::ACTION_USR_PROFILES:
                return $curUserProfile->isMgmProfiles();
            case self::ACTION_MGM_APITOKENS:
                return $curUserProfile->isMgmApiTokens();
            case self::ACTION_EVL:
                return $curUserProfile->isEvl();
        }

        Log::writeNewLog(__FUNCTION__, sprintf(_('Denegado acceso a %s'), self::getActionName($action)), Log::NOTICE);

        return false;
    }

    /**
     * Obtener el nombre de la acción indicada
     *
     * @param int  $action    El id de la acción
     * @param bool $shortName Si se devuelve el nombre corto de la acción
     * @return string
     */
    public static function getActionName($action, $shortName = false)
    {
        $actionName = [
            self::ACTION_ACC_SEARCH => ['acc_search', _('Buscar Cuentas')],
            self::ACTION_ACC_VIEW => ['acc_view', _('Ver Cuenta')],
            self::ACTION_ACC_COPY => ['acc_copy', _('Copiar Cuenta')],
            self::ACTION_ACC_NEW => ['acc_new', _('Nueva Cuenta')],
            self::ACTION_ACC_EDIT => ['acc_edit', _('Editar Cuenta')],
            self::ACTION_ACC_EDIT_PASS => ['acc_editpass', _('Editar Clave de Cuenta')],
            self::ACTION_ACC_VIEW_HISTORY => ['acc_viewhist', _('Ver Historial')],
            self::ACTION_ACC_VIEW_PASS => ['acc_viewpass', _('Ver Clave')],
            self::ACTION_ACC_DELETE => ['acc_delete', _('Eliminar Cuenta')],
            self::ACTION_ACC_FILES => ['acc_files', _('Archivos')],
            self::ACTION_ACC_REQUEST => ['acc_request', _('Peticiones')],
            self::ACTION_MGM => ['mgm', _('Gestión Aplicación')],
            self::ACTION_MGM_CATEGORIES => ['mgm_categories', _('Gestión Categorías')],
            self::ACTION_MGM_CUSTOMERS => ['mgm_customers', _('Gestión Clientes')],
            self::ACTION_MGM_CUSTOMFIELDS => ['mgm_customfields', _('Gestión Campos Personalizados')],
            self::ACTION_MGM_APITOKENS => ['mgm_apitokens', _('Gestión Autorizaciones API')],
            self::ACTION_MGM_FILES  => ['mgm_files', _('Gestión de Archivos')],
            self::ACTION_MGM_ACCOUNTS  => ['mgm_accounts', _('Gestión de Cuentas')],
            self::ACTION_MGM_TAGS  => ['mgm_tags', _('Gestión de Etiquetas')],
            self::ACTION_USR => ['usr', _('Gestión Usuarios')],
            self::ACTION_USR_USERS => ['usr_users', _('Gestión Usuarios')],
            self::ACTION_USR_GROUPS => ['usr_groups', _('Gestión Grupos')],
            self::ACTION_USR_PROFILES => ['usr_profiles', _('Gestión Perfiles')],
            self::ACTION_CFG => ['cfg', _('Configuración')],
            self::ACTION_CFG_GENERAL => ['cfg_general', _('Configuración General')],
            self::ACTION_CFG_ENCRYPTION => ['cfg_encryption', _('Encriptación')],
            self::ACTION_CFG_BACKUP => ['cfg_backup', _('Copia de Seguridad')],
            self::ACTION_CFG_EXPORT => ['cfg_export', _('Exportar')],
            self::ACTION_CFG_IMPORT => ['cfg_import', _('Importar')],
            self::ACTION_EVL => 'evl'
        ];

        if (!isset($actionName[$action])) {
            return $action;
        }

        if ($shortName) {
            return $actionName[$action][0];
        }

        return $actionName[$action][1];
    }

    /**
     * Comprueba los permisos de acceso a una cuenta.
     *
     * @param string         $module      con la acción realizada
     * @param AccountExtData $accountData con los datos de la cuenta a verificar
     * @return bool
     */
    public static function checkAccountAccess($module, AccountExtData $accountData)
    {
        if (Session::getUserData()->isUserIsAdminApp()
            || Session::getUserData()->isUserIsAdminAcc()
        ) {
            return true;
        }

        $userId = Session::getUserData()->getUserId();
        $userGroupId = Session::getUserData()->getUserGroupId();
        $userInGroups = self::getIsUserInGroups($accountData);
        $userInUsers = in_array($userId, $accountData->getAccountUsersId());

        switch ($module) {
            case self::ACTION_ACC_VIEW:
            case self::ACTION_ACC_VIEW_PASS:
            case self::ACTION_ACC_VIEW_HISTORY:
            case self::ACTION_ACC_COPY:
                return ($userId === $accountData->getAccountUserId()
                    || $userGroupId === $accountData->getAccountUserGroupId()
                    || $userInUsers
                    || $userInGroups);
            case self::ACTION_ACC_EDIT:
            case self::ACTION_ACC_DELETE:
            case self::ACTION_ACC_EDIT_PASS:
                return ($userId === $accountData->getAccountUserId()
                    || $userGroupId === $accountData->getAccountUserGroupId()
                    || ($userInUsers && $accountData->getAccountOtherUserEdit())
                    || ($userInGroups && $accountData->getAccountOtherGroupEdit()));
            default:
                return false;
        }
    }

    /**
     * Comprobar si el usuario o el grupo del usuario se encuentran los grupos asociados a la
     * cuenta.
     *
     * @param AccountExtData $AccountData $AccountData
     * @return bool
     */
    private static function getIsUserInGroups(AccountExtData $AccountData)
    {
        // Comprobar si el usuario está vinculado desde un grupo
        foreach (GroupUsers::getItem()->getById($AccountData->getAccountUserGroupId()) as $GroupUsersData) {
            if ($GroupUsersData->getUsertogroupUserId() === Session::getUserData()->getUserId()) {
                return true;
            }
        }

        // Comprobar si el grupo del usuario está vinculado como grupo secundario de la cuenta
        foreach ($AccountData->getUserGroupsId() as $groupId) {
            if ($groupId === Session::getUserData()->getUserGroupId()) {
                return true;
            }
        }

        return false;
    }
}