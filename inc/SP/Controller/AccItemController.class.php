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

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Api\ApiTokensUtil;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\DataModel\CustomFieldData;
use SP\DataModel\GroupData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\Log\Log;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\Groups\GroupUsers;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Profiles\ProfileUtil;
use SP\Mgmt\Users\User;
use SP\Util\Checks;

/**
 * Class AccItemMgmt
 *
 * @package SP\Controller
 */
class AccItemController extends ControllerBase implements ActionsInterface
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var int
     */
    private $module = 0;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la ficha de usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getUser()
    {
        $this->module = self::ACTION_USR_USERS;
        $this->view->addTemplate('users');

        $this->view->assign('user', $this->view->itemId ? User::getItem()->getById($this->view->itemId) : new UserData());
        $this->view->assign('isDisabled', ((User::getItem()->getItemData()->getUserLogin() === 'demo' && $this->view->isDemo) || $this->view->actionId === self::ACTION_USR_USERS_VIEW) ? 'disabled' : '');
        $this->view->assign('groups', Group::getItem()->getItemsForSelect());
        $this->view->assign('profiles', Profile::getItem()->getItemsForSelect());

        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        $this->view->assign('customFields', CustomField::getItem(new CustomFieldData($this->module))->getById($this->view->itemId));
    }

    /**
     * Obtener los datos para la ficha de grupo
     */
    public function getGroup()
    {
        $this->module = self::ACTION_USR_GROUPS;
        $this->view->addTemplate('groups');

        $this->view->assign('group', $this->view->itemId ? Group::getItem()->getById($this->view->itemId) : new GroupData());
        $this->view->assign('users', User::getItem()->getItemsForSelect());
        $this->view->assign('groupUsers', GroupUsers::getItem()->getById($this->view->itemId));

        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener los datos para la ficha de perfil
     */
    public function getProfile()
    {
        $this->module = self::ACTION_USR_PROFILES;
        $this->view->addTemplate('profiles');

        $Profile = $this->view->itemId ? Profile::getItem()->getById($this->view->itemId) : new ProfileData();

        $this->view->assign('profile', $Profile);
        $this->view->assign('isDisabled', ($this->view->actionId === self::ACTION_USR_PROFILES_VIEW) ? 'disabled' : '');

        if ($this->view->isView === true) {
            $users = ProfileUtil::getProfileInUsersName($this->view->itemId);

            if (count($users) > 0) {
                $usedBy = [];

                foreach ($users as $user) {
                    $usedBy[] = $user->user_login;
                }

                $usedBy = implode(' | ', $usedBy);
            } else {
                $usedBy = _('No usado');
            }

            $this->view->assign('usedBy', $usedBy);
        }
    }

    /**
     * Inicializar la vista de cambio de clave de usuario
     */
    public function getUserPass()
    {
        $this->module = self::ACTION_USR_USERS;
        $this->setAction(self::ACTION_USR_USERS_EDITPASS);

        // Comprobar si el usuario a modificar es distinto al de la sesión
        if ($this->view->itemId !== Session::getUserId() && !$this->checkAccess()) {
            return;
        }

        $this->view->assign('user', User::getItem()->getById($this->view->itemId));
        $this->view->addTemplate('userspass');
    }

    /**
     * Obtener los datos para la ficha de tokens de API
     */
    public function getToken()
    {
        $this->module = self::ACTION_MGM_APITOKENS;
        $this->view->addTemplate('tokens');

        $token = ApiTokensUtil::getTokens($this->view->itemId, true);

        $this->view->assign('users', User::getItem()->getItemsForSelect());
        $this->view->assign('actions', ApiTokensUtil::getTokenActions());
        $this->view->assign('token', $token);
        $this->view->assign('gotData', is_object($token));

        if ($this->view->isView === true) {
            $msg = sprintf('%s ;;Usuario: %s', _('Token de autorización visualizado'), $token->user_login);
            Log::writeNewLogAndEmail(_('Autorizaciones'), $msg);
        }
    }

    /**
     * Obtener los datos para la ficha de enlace público
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getPublicLink()
    {
        $this->module = self::ACTION_MGM_PUBLICLINKS;
        $this->view->addTemplate('publiclinks');

        $this->view->assign('link', PublicLink::getItem()->getById($this->view->itemId));
    }
}