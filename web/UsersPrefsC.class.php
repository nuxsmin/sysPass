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

use SP\Auth\Auth2FA;
use SP\Session;
use SP\UserPreferences;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class PreferencesC encargada de mostrar las preferencias de los usuarios
 *
 * @package SP\Controller
 */
class UsersPrefsC extends Controller implements ActionsInterface
{
    private $_tabIndex = 0;

    /**
     * Constructor
     *
     * @param $template \SP\Template con instancia de plantilla
     */
    public function __construct(\SP\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('tabs', array());
        $this->view->assign('sk', \SP\Common::getSessionKey(true));
    }

    /**
     * Obtener la pestaña de seguridad
     */
    public function getSecurityTab()
    {
        $this->setAction(self::ACTION_USR_PREFERENCES_SECURITY);

//        if (!$this->checkAccess()) {
//            $this->showError(self::ERR_PAGE_NO_PERMISSION);
//            return;
//        }

        $this->view->addTemplate('security');

        $userId = Session::getUserId();

        $userPrefs = UserPreferences::getPreferences($userId);

        $twoFa = new Auth2FA($userId, Session::getUserLogin());

        $this->view->assign('userId', $userId);

        if (!$userPrefs->isUse2Fa()) {
            $this->view->assign('qrCode', $twoFa->getUserQRCode());
        }

        $this->view->assign('chk2FAEnabled', $userPrefs->isUse2Fa());

        $this->view->append('tabs', array('title' => _('Seguridad')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'security');
        $this->view->assign('actionId', $this->getAction(), 'security');
    }

    /**
     * Obtener el índice actual de las pestañas
     *
     * @return int
     */
    private function getTabIndex(){
        $index = $this->_tabIndex;
        $this->_tabIndex++;

        return $index;
    }
}