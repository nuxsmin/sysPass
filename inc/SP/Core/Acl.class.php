<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core;

use SP\DataModel\AccountExtData;
use SP\DataModel\UserData;
use SP\Log\Log;
use SP\Mgmt\Groups\GroupUsers;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de calcular las access lists de acceso a usuarios.
 */
class Acl implements ActionsInterface
{
    /**
     * @var int
     */
    protected $actionId;

    /**
     * Acl constructor.
     *
     * @param int $actionId
     */
    public function __construct($actionId = null)
    {
        $this->actionId = $actionId;
    }

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
            case self::ACTION_MGM_PLUGINS:
            case self::ACTION_CFG_ACCOUNTS:
                return $curUserProfile->isConfigGeneral();
            case self::ACTION_CFG_IMPORT:
                return $curUserProfile->isConfigImport();
            case self::ACTION_MGM_CATEGORIES:
            case self::ACTION_MGM_CATEGORIES_SEARCH:
                return $curUserProfile->isMgmCategories();
            case self::ACTION_MGM_CUSTOMERS:
            case self::ACTION_MGM_CUSTOMERS_SEARCH:
                return $curUserProfile->isMgmCustomers();
            case self::ACTION_MGM_CUSTOMFIELDS:
            case self::ACTION_MGM_CUSTOMFIELDS_SEARCH:
                return $curUserProfile->isMgmCustomFields();
            case self::ACTION_MGM_PUBLICLINKS:
            case self::ACTION_MGM_PUBLICLINKS_SEARCH:
                return $curUserProfile->isMgmPublicLinks();
            case self::ACTION_MGM_PUBLICLINKS_NEW:
                return ($curUserProfile->isMgmPublicLinks() || $curUserProfile->isAccPublicLinks());
            case self::ACTION_MGM_ACCOUNTS:
            case self::ACTION_MGM_ACCOUNTS_SEARCH:
            case self::ACTION_MGM_ACCOUNTS_HISTORY:
            case self::ACTION_MGM_ACCOUNTS_SEARCH_HISTORY:
                return $curUserProfile->isMgmAccounts();
            case self::ACTION_MGM_FILES:
            case self::ACTION_MGM_FILES_SEARCH:
                return $curUserProfile->isMgmFiles();
            case self::ACTION_MGM_TAGS:
            case self::ACTION_MGM_TAGS_SEARCH:
                return $curUserProfile->isMgmTags();
            case self::ACTION_CFG_ENCRYPTION:
                return $curUserProfile->isConfigEncryption();
            case self::ACTION_CFG_BACKUP:
                return $curUserProfile->isConfigBackup();
            case self::ACTION_USR:
                return ($curUserProfile->isMgmUsers() || $curUserProfile->isMgmGroups() || $curUserProfile->isMgmProfiles());
            case self::ACTION_USR_USERS:
            case self::ACTION_USR_USERS_SEARCH:
                return $curUserProfile->isMgmUsers();
            case self::ACTION_USR_USERS_EDITPASS:
                return ($userId === $curUserId || $curUserProfile->isMgmUsers());
            case self::ACTION_USR_GROUPS:
            case self::ACTION_USR_GROUPS_SEARCH:
                return $curUserProfile->isMgmGroups();
            case self::ACTION_USR_PROFILES:
            case self::ACTION_USR_PROFILES_SEARCH:
                return $curUserProfile->isMgmProfiles();
            case self::ACTION_MGM_APITOKENS:
            case self::ACTION_MGM_APITOKENS_SEARCH:
                return $curUserProfile->isMgmApiTokens();
            case self::ACTION_EVL:
                return $curUserProfile->isEvl();
            case self::ACTION_NOT:
            case self::ACTION_NOT_USER:
                return true;
        }

        $Log = new Log();
        $Log->getLogMessage()
            ->setAction(__FUNCTION__)
            ->addDetails(__('Acceso denegado', false), self::getActionName($action, false, false));
        $Log->setLogLevel(Log::NOTICE);
        $Log->writeLog();

        return false;
    }

    /**
     * Obtener el nombre de la acción indicada
     *
     * @param int  $action    El id de la acción
     * @param bool $shortName Si se devuelve el nombre corto de la acción
     * @param bool $translate
     * @return string
     */
    public static function getActionName($action, $shortName = false, $translate = true)
    {
        $actionName = [
            self::ACTION_ACC_SEARCH => ['acc_search', __('Buscar Cuentas', $translate)],
            self::ACTION_ACC_VIEW => ['acc_view', __('Ver Cuenta', $translate)],
            self::ACTION_ACC_COPY => ['acc_copy', __('Copiar Cuenta', $translate)],
            self::ACTION_ACC_NEW => ['acc_new', __('Nueva Cuenta', $translate)],
            self::ACTION_ACC_EDIT => ['acc_edit', __('Editar Cuenta', $translate)],
            self::ACTION_ACC_EDIT_PASS => ['acc_editpass', __('Editar Clave de Cuenta', $translate)],
            self::ACTION_ACC_VIEW_HISTORY => ['acc_viewhist', __('Ver Historial', $translate)],
            self::ACTION_ACC_VIEW_PASS => ['acc_viewpass', __('Ver Clave', $translate)],
            self::ACTION_ACC_DELETE => ['acc_delete', __('Eliminar Cuenta', $translate)],
            self::ACTION_ACC_FILES => ['acc_files', __('Archivos', $translate)],
            self::ACTION_ACC_REQUEST => ['acc_request', __('Peticiones', $translate)],
            self::ACTION_MGM => ['mgm', __('Gestión Aplicación', $translate)],
            self::ACTION_MGM_CATEGORIES => ['mgm_categories', __('Gestión Categorías', $translate)],
            self::ACTION_MGM_CATEGORIES_SEARCH => ['mgm_categories_search', __('Buscar Categorías', $translate)],
            self::ACTION_MGM_CATEGORIES_NEW => ['mgm_categories_add', __('Añadir Categoría', $translate)],
            self::ACTION_MGM_CATEGORIES_EDIT => ['mgm_categories_edit', __('Editar Categoría', $translate)],
            self::ACTION_MGM_CATEGORIES_DELETE => ['mgm_categories_delete', __('Eliminar Categoría', $translate)],
            self::ACTION_MGM_CUSTOMERS => ['mgm_customers', __('Gestión Clientes', $translate)],
            self::ACTION_MGM_CUSTOMERS_SEARCH => ['mgm_customers', __('Buscar Clientes', $translate)],
            self::ACTION_MGM_CUSTOMERS_NEW => ['mgm_customers_add', __('Añadir Cliente', $translate)],
            self::ACTION_MGM_CUSTOMERS_EDIT => ['mgm_customers_edit', __('Editar Cliente', $translate)],
            self::ACTION_MGM_CUSTOMERS_DELETE => ['mgm_customers_delete', __('Eliminar Cliente', $translate)],
            self::ACTION_MGM_CUSTOMFIELDS => ['mgm_customfields', __('Gestión Campos Personalizados', $translate)],
            self::ACTION_MGM_APITOKENS => ['mgm_apitokens', __('Gestión Autorizaciones API', $translate)],
            self::ACTION_MGM_FILES => ['mgm_files', __('Gestión de Archivos', $translate)],
            self::ACTION_MGM_ACCOUNTS => ['mgm_accounts', __('Gestión de Cuentas', $translate)],
            self::ACTION_MGM_TAGS => ['mgm_tags', __('Gestión de Etiquetas', $translate)],
            self::ACTION_USR => ['usr', __('Gestión Usuarios', $translate)],
            self::ACTION_USR_USERS => ['usr_users', __('Gestión Usuarios', $translate)],
            self::ACTION_USR_GROUPS => ['usr_groups', __('Gestión Grupos', $translate)],
            self::ACTION_USR_PROFILES => ['usr_profiles', __('Gestión Perfiles', $translate)],
            self::ACTION_CFG => ['cfg', __('Configuración', $translate)],
            self::ACTION_CFG_GENERAL => ['cfg_general', __('Configuración General', $translate)],
            self::ACTION_CFG_ENCRYPTION => ['cfg_encryption', __('Encriptación', $translate)],
            self::ACTION_CFG_BACKUP => ['cfg_backup', __('Copia de Seguridad', $translate)],
            self::ACTION_CFG_EXPORT => ['cfg_export', __('Exportar', $translate)],
            self::ACTION_CFG_IMPORT => ['cfg_import', __('Importar', $translate)],
            self::ACTION_EVL => ['cfg_evl', __('Log de Eventos', $translate)]
        ];

        if (!isset($actionName[$action])) {
            return $action;
        }

        if ($shortName) {
            return $actionName[$action][0];
        }

        return $actionName[$action][1];
    }
}