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
use SP\Log\Log;

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
        return self::$action->getActionById($actionId)->getRoute();
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
        $curUserProfile = $this->session->getUserProfile();

        // Comprobamos si la cache de permisos está inicializada
        if (!is_object($curUserProfile)) {
//            error_log('ACL_CACHE_MISS');
            return false;
        }

        $userData = $this->session->getUserData();

        if ($userData->getIsAdminApp()) {
            return true;
        }

        switch ($action) {
            case self::ACCOUNT_VIEW:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccView() || $curUserProfile->isAccEdit());
            case self::ACCOUNT_VIEW_PASS:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccViewPass());
            case self::ACCOUNT_VIEW_HISTORY:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccViewHistory());
            case self::ACCOUNT_EDIT:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccEdit());
            case self::ACCOUNT_EDIT_PASS:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccEditPass());
            case self::ACCOUNT_CREATE:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccAdd());
            case self::ACCOUNT_COPY:
                return ($userData->getIsAdminAcc() || ($curUserProfile->isAccAdd() && $curUserProfile->isAccView()));
            case self::ACCOUNT_DELETE:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccDelete());
            case self::ACCOUNT_FILE:
                return ($userData->getIsAdminAcc() || $curUserProfile->isAccFiles());
            case self::ITEMS_MANAGE:
                return ($curUserProfile->isMgmCategories() || $curUserProfile->isMgmCustomers());
            case self::CONFIG:
                return ($curUserProfile->isConfigGeneral() || $curUserProfile->isConfigEncryption() || $curUserProfile->isConfigBackup() || $curUserProfile->isConfigImport());
            case self::CONFIG_GENERAL:
            case self::PLUGIN:
            case self::ACCOUNT_CONFIG:
                return $curUserProfile->isConfigGeneral();
            case self::IMPORT_CONFIG:
                return $curUserProfile->isConfigImport();
            case self::CATEGORY:
            case self::CATEGORY_SEARCH:
                return $curUserProfile->isMgmCategories();
            case self::CLIENT:
            case self::CLIENT_SEARCH:
                return $curUserProfile->isMgmCustomers();
            case self::CUSTOMFIELD:
            case self::CUSTOMFIELD_SEARCH:
                return $curUserProfile->isMgmCustomFields();
            case self::PUBLICLINK:
            case self::PUBLICLINK_SEARCH:
                return $curUserProfile->isMgmPublicLinks();
            case self::PUBLICLINK_CREATE:
                return ($curUserProfile->isMgmPublicLinks() || $curUserProfile->isAccPublicLinks());
            case self::ACCOUNTMGR:
            case self::ACCOUNTMGR_SEARCH:
            case self::ACCOUNTMGR_HISTORY:
            case self::ACCOUNTMGR_SEARCH_HISTORY:
                return $curUserProfile->isMgmAccounts();
            case self::FILE:
            case self::FILE_SEARCH:
                return $curUserProfile->isMgmFiles();
            case self::TAG:
            case self::TAG_SEARCH:
                return $curUserProfile->isMgmTags();
            case self::ENCRYPTION_CONFIG:
                return $curUserProfile->isConfigEncryption();
            case self::BACKUP_CONFIG:
                return $curUserProfile->isConfigBackup();
            case self::ACCESS_MANAGE:
                return ($curUserProfile->isMgmUsers() || $curUserProfile->isMgmGroups() || $curUserProfile->isMgmProfiles());
            case self::USER:
            case self::USER_SEARCH:
            case self::USER_CREATE:
            case self::USER_EDIT:
                return $curUserProfile->isMgmUsers();
            case self::USER_EDIT_PASS:
                // Comprobar si el usuario es distinto al de la sesión
                return ($userId === $userData->getId() || $curUserProfile->isMgmUsers());
            case self::GROUP:
            case self::GROUP_SEARCH:
                return $curUserProfile->isMgmGroups();
            case self::PROFILE:
            case self::PROFILE_SEARCH:
                return $curUserProfile->isMgmProfiles();
            case self::APITOKEN:
            case self::APITOKEN_SEARCH:
                return $curUserProfile->isMgmApiTokens();
            case self::EVENTLOG:
                return $curUserProfile->isEvl();
            case self::NOTICE:
            case self::NOTICE_USER:
            case self::NOTICE_USER_SEARCH:
                return true;
        }

        $Log = new Log();
        $Log->getLogMessage()
            ->setAction(__FUNCTION__)
            ->addDetails(__('Acceso denegado', false), self::getActionInfo($action, false));
        $Log->setLogLevel(Log::NOTICE);
        $Log->writeLog();

        return false;
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
        $text = self::$action->getActionById($actionId)->getText();

        return $translate ? __($text) : $text;
    }
}