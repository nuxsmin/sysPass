<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de calcular las access lists de acceso a usuarios.
 */
final class Acl implements ActionsInterface
{
    /**
     * @var Actions
     */
    protected static $action;
    /**
     * @var SessionContext
     */
    private $context;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * Acl constructor.
     *
     * @param ContextInterface $context
     * @param EventDispatcher  $eventDispatcher
     * @param Actions|null     $action
     */
    public function __construct(ContextInterface $context, EventDispatcher $eventDispatcher, Actions $action = null)
    {
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;

        self::$action = $action;
    }

    /**
     * Returns action route
     *
     * @param $actionId
     *
     * @return string
     */
    public static function getActionRoute($actionId)
    {
        try {
            return self::$action !== null ? self::$action->getActionById($actionId)->getRoute() : '';
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
     *
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
     *
     * @param int $action con el Id de la acción
     * @param int $userId opcional, con el Id del usuario
     *
     * @return bool
     */
    public function checkUserAccess($action, $userId = 0)
    {
        if (!($userProfile = $this->context->getUserProfile())) {
            return false;
        }

        $userData = $this->context->getUserData();

        if ($userData->getIsAdminApp()) {
            return true;
        }

        switch ($action) {
            case self::ACCOUNT_VIEW:
                return ($userData->getIsAdminAcc() || $userProfile->isAccView() || $userProfile->isAccEdit());
            case self::ACCOUNT_VIEW_PASS:
                return ($userData->getIsAdminAcc() || $userProfile->isAccViewPass());
            case self::ACCOUNT_HISTORY_VIEW:
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
                return ($userData->getIsAdminAcc()
                    || $userProfile->isMgmCategories()
                    || $userProfile->isMgmCustomers()
                    || $userProfile->isMgmAccounts()
                    || $userProfile->isMgmFiles()
                    || $userProfile->isMgmTags()
                    || $userProfile->isMgmCustomFields()
                    || $userProfile->isMgmPublicLinks());
            case self::CONFIG:
                return ($userProfile->isConfigGeneral()
                    || $userProfile->isConfigEncryption()
                    || $userProfile->isConfigBackup()
                    || $userProfile->isConfigImport());
            case self::CONFIG_GENERAL:
            case self::CONFIG_ACCOUNT:
            case self::CONFIG_WIKI:
            case self::CONFIG_LDAP:
            case self::CONFIG_MAIL:
            case self::PLUGIN:
            case self::PLUGIN_SEARCH:
            case self::PLUGIN_DISABLE:
            case self::PLUGIN_ENABLE:
            case self::PLUGIN_RESET:
            case self::PLUGIN_VIEW:
                return $userProfile->isConfigGeneral();
            case self::CONFIG_IMPORT:
                return $userProfile->isConfigImport();
            case self::CATEGORY:
            case self::CATEGORY_SEARCH:
            case self::CATEGORY_VIEW:
            case self::CATEGORY_CREATE:
            case self::CATEGORY_EDIT:
            case self::CATEGORY_DELETE:
                return $userProfile->isMgmCategories();
            case self::CLIENT:
            case self::CLIENT_SEARCH:
            case self::CLIENT_VIEW:
            case self::CLIENT_CREATE:
            case self::CLIENT_EDIT:
            case self::CLIENT_DELETE:
                return $userProfile->isMgmCustomers();
            case self::CUSTOMFIELD:
            case self::CUSTOMFIELD_SEARCH:
            case self::CUSTOMFIELD_VIEW:
            case self::CUSTOMFIELD_CREATE:
            case self::CUSTOMFIELD_EDIT:
            case self::CUSTOMFIELD_DELETE:
                return $userProfile->isMgmCustomFields();
            case self::PUBLICLINK:
            case self::PUBLICLINK_SEARCH:
            case self::PUBLICLINK_VIEW:
            case self::PUBLICLINK_EDIT:
            case self::PUBLICLINK_DELETE:
                return $userProfile->isMgmPublicLinks();
            case self::PUBLICLINK_CREATE:
            case self::PUBLICLINK_REFRESH:
                return ($userProfile->isMgmPublicLinks() || $userProfile->isAccPublicLinks());
            case self::ACCOUNTMGR:
            case self::ACCOUNTMGR_SEARCH:
            case self::ACCOUNTMGR_HISTORY:
            case self::ACCOUNTMGR_HISTORY_SEARCH:
                return ($userData->getIsAdminAcc() || $userProfile->isMgmAccounts());
            case self::FILE:
            case self::FILE_SEARCH:
            case self::FILE_DELETE:
            case self::FILE_VIEW:
            case self::FILE_DOWNLOAD:
                return $userProfile->isMgmFiles();
            case self::TAG:
            case self::TAG_SEARCH:
            case self::TAG_VIEW:
            case self::TAG_CREATE:
            case self::TAG_EDIT:
            case self::TAG_DELETE:
                return $userProfile->isMgmTags();
            case self::CONFIG_CRYPT:
                return $userProfile->isConfigEncryption();
            case self::CONFIG_BACKUP:
                return $userProfile->isConfigBackup();
            case self::ACCESS_MANAGE:
                return ($userProfile->isMgmUsers()
                    || $userProfile->isMgmGroups()
                    || $userProfile->isMgmProfiles()
                    || $userProfile->isMgmApiTokens());
            case self::SECURITY_MANAGE:
                return $userProfile->isEvl()
                    || $userProfile->isMgmUsers();
            case self::USER:
            case self::USER_SEARCH:
            case self::USER_VIEW:
            case self::USER_CREATE:
            case self::USER_EDIT:
            case self::USER_DELETE:
            case self::TRACK:
            case self::TRACK_SEARCH:
            case self::TRACK_CLEAR:
            case self::TRACK_UNLOCK:
                return $userProfile->isMgmUsers();
            case self::USER_EDIT_PASS:
                // Comprobar si el usuario es distinto al de la sesión
                return ($userId === $userData->getId() || $userProfile->isMgmUsers());
            case self::GROUP:
            case self::GROUP_SEARCH:
            case self::GROUP_VIEW:
            case self::GROUP_CREATE:
            case self::GROUP_EDIT:
            case self::GROUP_DELETE:
                return $userProfile->isMgmGroups();
            case self::PROFILE:
            case self::PROFILE_SEARCH:
            case self::PROFILE_VIEW:
            case self::PROFILE_CREATE:
            case self::PROFILE_EDIT:
            case self::PROFILE_DELETE:
                return $userProfile->isMgmProfiles();
            case self::AUTHTOKEN:
            case self::AUTHTOKEN_SEARCH:
            case self::AUTHTOKEN_VIEW:
            case self::AUTHTOKEN_CREATE:
            case self::AUTHTOKEN_EDIT:
            case self::AUTHTOKEN_DELETE:
                return $userProfile->isMgmApiTokens();
            case self::ITEMPRESET:
            case self::ITEMPRESET_SEARCH:
            case self::ITEMPRESET_VIEW:
            case self::ITEMPRESET_CREATE:
            case self::ITEMPRESET_EDIT:
            case self::ITEMPRESET_DELETE:
                return $userProfile->isMgmItemsPreset();
            case self::EVENTLOG:
            case self::EVENTLOG_SEARCH:
            case self::EVENTLOG_CLEAR:
                return $userProfile->isEvl();
            case self::CUSTOMFIELD_VIEW_PASS:
                return ($userData->getIsAdminApp() || $userProfile->isAccViewPass());
            case self::ACCOUNT_REQUEST:
            case self::NOTIFICATION:
            case self::NOTIFICATION_VIEW:
            case self::NOTIFICATION_SEARCH:
            case self::NOTIFICATION_CHECK:
                return true;
        }

        try {
            $actionName = self::$action->getActionById($action)->getName();
        } catch (ActionNotFoundException $e) {
            $actionName = __u('N/A');
        }

        $this->eventDispatcher->notifyEvent('acl.deny',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Access denied'))
                ->addDetail(__u('Action'), $actionName)
                ->addDetail(__u('User'), $userData->getLogin()))
        );

        return false;
    }
}