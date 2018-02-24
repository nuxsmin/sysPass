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

namespace SP\Core\Acl;

use SP\Core\Session\Session;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de calcular las access lists de acceso a usuarios.
 */
class Acl implements ActionsInterface
{
    /**
     * @var Actions
     */
    protected static $action;
    /**
     * @var Session
     */
    private $session;

    /**
     * Acl constructor.
     *
     * @param Session      $session
     * @param Actions|null $action
     */
    public function __construct(Session $session, Actions $action = null)
    {
        $this->session = $session;

        self::$action = $action;
    }

    /**
     * Returns action route
     *
     * @param $actionId
     * @return string
     */
    public static function getActionRoute($actionId)
    {
        try {
            return self::$action->getActionById($actionId)->getRoute();
        } catch (ActionNotFoundException $e) {
            processException($e);
        }

        return '';
    }

    /**
     * Obtener el nombre de la acción indicada
     *
     * @param int  $actionId El id de la acción
     * @param bool $translate
     * @return string
     * @internal param bool $shortName Si se devuelve el nombre corto de la acción
     */
    public static function getActionInfo($actionId, $translate = true)
    {
        try {
            $text = self::$action->getActionById($actionId)->getText();
            return $translate ? __($text) : $text;
        } catch (ActionNotFoundException $e) {
            processException($e);
        }

        return '';
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
    public function checkUserAccess($action, $userId = 0)
    {
        $userProfile = $this->session->getUserProfile();

        // Comprobamos si la cache de permisos está inicializada
        if (!is_object($userProfile)) {
//            error_log('ACL_CACHE_MISS');
            return false;
        }

        $userData = $this->session->getUserData();

        if ($userData->getIsAdminApp()) {
            return true;
        }

        switch ($action) {
            case self::ACCOUNT_VIEW:
                return ($userData->getIsAdminAcc() || $userProfile->isAccView() || $userProfile->isAccEdit());
            case self::ACCOUNT_VIEW_PASS:
                return ($userData->getIsAdminAcc() || $userProfile->isAccViewPass());
            case self::ACCOUNT_VIEW_HISTORY:
                return ($userData->getIsAdminAcc() || $userProfile->isAccViewHistory());
            case self::ACCOUNT_EDIT:
                return ($userData->getIsAdminAcc() || $userProfile->isAccEdit());
            case self::ACCOUNT_EDIT_PASS:
                return ($userData->getIsAdminAcc() || $userProfile->isAccEditPass());
            case self::ACCOUNT_CREATE:
                return ($userData->getIsAdminAcc() || $userProfile->isAccAdd());
            case self::ACCOUNT_COPY:
                return ($userData->getIsAdminAcc() || ($userProfile->isAccAdd() && $userProfile->isAccView()));
            case self::ACCOUNT_DELETE:
                return ($userData->getIsAdminAcc() || $userProfile->isAccDelete());
            case self::ACCOUNT_FILE:
                return ($userData->getIsAdminAcc() || $userProfile->isAccFiles());
            case self::ITEMS_MANAGE:
                return ($userProfile->isMgmCategories() || $userProfile->isMgmCustomers());
            case self::CONFIG:
                return ($userProfile->isConfigGeneral() || $userProfile->isConfigEncryption() || $userProfile->isConfigBackup() || $userProfile->isConfigImport());
            case self::CONFIG_GENERAL:
            case self::PLUGIN:
            case self::ACCOUNT_CONFIG:
                return $userProfile->isConfigGeneral();
            case self::IMPORT_CONFIG:
                return $userProfile->isConfigImport();
            case self::CATEGORY:
            case self::CATEGORY_SEARCH:
                return $userProfile->isMgmCategories();
            case self::CLIENT:
            case self::CLIENT_SEARCH:
                return $userProfile->isMgmCustomers();
            case self::CUSTOMFIELD:
            case self::CUSTOMFIELD_SEARCH:
                return $userProfile->isMgmCustomFields();
            case self::PUBLICLINK:
            case self::PUBLICLINK_SEARCH:
                return $userProfile->isMgmPublicLinks();
            case self::PUBLICLINK_CREATE:
                return ($userProfile->isMgmPublicLinks() || $userProfile->isAccPublicLinks());
            case self::ACCOUNTMGR:
            case self::ACCOUNTMGR_SEARCH:
            case self::ACCOUNTMGR_HISTORY:
            case self::ACCOUNTMGR_SEARCH_HISTORY:
                return $userProfile->isMgmAccounts();
            case self::FILE:
            case self::FILE_SEARCH:
                return $userProfile->isMgmFiles();
            case self::TAG:
            case self::TAG_SEARCH:
                return $userProfile->isMgmTags();
            case self::ENCRYPTION_CONFIG:
                return $userProfile->isConfigEncryption();
            case self::BACKUP_CONFIG:
                return $userProfile->isConfigBackup();
            case self::ACCESS_MANAGE:
                return ($userProfile->isMgmUsers() || $userProfile->isMgmGroups() || $userProfile->isMgmProfiles());
            case self::USER:
            case self::USER_SEARCH:
            case self::USER_CREATE:
            case self::USER_EDIT:
                return $userProfile->isMgmUsers();
            case self::USER_EDIT_PASS:
                // Comprobar si el usuario es distinto al de la sesión
                return ($userId === $userData->getId() || $userProfile->isMgmUsers());
            case self::GROUP:
            case self::GROUP_SEARCH:
                return $userProfile->isMgmGroups();
            case self::PROFILE:
            case self::PROFILE_SEARCH:
                return $userProfile->isMgmProfiles();
            case self::APITOKEN:
            case self::APITOKEN_SEARCH:
                return $userProfile->isMgmApiTokens();
            case self::EVENTLOG:
            case self::EVENTLOG_SEARCH:
                return $userProfile->isEvl();
            case self::NOTICE:
            case self::NOTICE_USER:
            case self::NOTICE_USER_SEARCH:
                return true;
        }

//        $Log = new Log();
//        $Log->getLogMessage()
//            ->setAction(__FUNCTION__)
//            ->addDetails(__('Acceso denegado', false), self::getActionInfo($action, false));
//        $Log->setLogLevel(Log::NOTICE);
//        $Log->writeLog();

        return false;
    }
}