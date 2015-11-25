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

use SP\Api\ApiTokens;
use SP\Api\ApiTokensUtil;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Log\Log;
use SP\Mgmt\CustomFields;
use SP\Mgmt\PublicLinkUtil;
use SP\Mgmt\User\Groups;
use SP\Mgmt\User\Profile;
use SP\Mgmt\User\ProfileUtil;
use SP\Mgmt\User\UserUtil;
use SP\Storage\DBUtil;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

class AccItemMgmt extends Controller implements ActionsInterface
{
    /**
     * Máximo numero de acciones antes de agrupar
     */
    const MAX_NUM_ACTIONS = 3;
    /**
     * @var int
     */
    private $_module = 0;
    /**
     * @var Icons
     */
    private $_icons;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $this->_icons = new Icons();
    }

    /**
     * Obtener los datos para la ficha de usuario
     */
    public function getUser()
    {
        $this->_module = self::ACTION_USR_USERS;
        $this->view->addTemplate('users');

        $this->view->assign('user', UserUtil::getUserData($this->view->itemId));
        $this->view->assign('isDisabled', (($this->view->user['user_login'] === 'demo' && $this->view->isDemo) || $this->view->actionId === self::ACTION_USR_USERS_VIEW) ? 'disabled' : '');
        $this->view->assign('groups', DBUtil::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
        $this->view->assign('profiles', DBUtil::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'));
        $this->view->assign('ro', ($this->view->user['checks']['user_isLdap']) ? 'READONLY' : '');

        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        // Se comprueba que hayan campos con valores para el elemento actual
        if ($this->view->itemId && CustomFields::checkCustomFieldExists($this->_module, $this->view->itemId)) {
            $this->view->assign('customFields', CustomFields::getCustomFieldsData($this->_module, $this->view->itemId));
        } else {
            $this->view->assign('customFields', CustomFields::getCustomFieldsForModule($this->_module));
        }
    }

    /**
     * Obtener los datos para la ficha de grupo
     */
    public function getGroup()
    {
        $this->_module = self::ACTION_USR_GROUPS;
        $this->view->addTemplate('groups');

        $this->view->assign('group', Groups::getGroupData($this->view->itemId));
        $this->view->assign('users', DBUtil::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('groupUsers', Groups::getUsersForGroup($this->view->itemId));

        $this->getCustomFieldsForItem();
    }

    /**
     * Obtener los datos para la ficha de perfil
     */
    public function getProfile()
    {
        $this->view->addTemplate('profiles');

        $profile = ($this->view->itemId) ? ProfileUtil::getProfile($this->view->itemId) : new Profile();

        $this->view->assign('profile', $profile);
        $this->view->assign('isDisabled', ($this->view->actionId === self::ACTION_USR_PROFILES_VIEW) ? 'disabled' : '');

        if ($this->view->isView === true) {
            $this->view->assign('usedBy', Profile::getProfileInUsersName($this->view->itemId));
        }
    }

    /**
     * Inicializar la vista de cambio de clave de usuario
     */
    public function getUserPass()
    {
        $this->setAction(self::ACTION_USR_USERS_EDITPASS);

        // Comprobar si el usuario a modificar es distinto al de la sesión
        if ($this->view->userId != Session::getUserId() && !$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('userspass');

        $this->view->assign('actionId', self::ACTION_USR_USERS_EDITPASS);

        // Obtener de nuevo el token de seguridad por si se habñia regenerado antes
        $this->view->assign('sk', SessionUtil::getSessionKey());
    }

    /**
     * Obtener los datos para la ficha de tokens de API
     */
    public function getToken()
    {
        $this->view->addTemplate('tokens');

        $token = ApiTokensUtil::getTokens($this->view->itemId, true);

        $this->view->assign('users', DBUtil::getValuesForSelect('usrData', 'user_id', 'user_name'));
        $this->view->assign('actions', ApiTokensUtil::getTokenActions());
        $this->view->assign('token', $token);
        $this->view->assign('gotData', is_object($token));

        if ($this->view->isView === true) {
            $msg = sprintf('%s ;;Usuario: %s', _('Token de autorización visualizado'), $token->user_login);
            Log::writeNewLogAndEmail(_('Autorizaciones'), $msg, null);
        }
    }

    /**
     * Obtener los datos para la ficha de enlace público
     */
    public function getPublicLink()
    {
        $this->view->addTemplate('publiclinks');

        $this->view->assign('link', PublicLinkUtil::getLinks($this->view->itemId)[0]);
    }


}