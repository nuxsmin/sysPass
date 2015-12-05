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

use SP\Auth\Auth2FA;
use SP\Core\ActionsInterface;
use SP\Core\Language;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Themes;
use SP\Mgmt\User\UserPreferences;


/**
 * Class PreferencesC encargada de mostrar las preferencias de los usuarios
 *
 * @package SP\Controller
 */
class UsersPrefs extends Controller implements ActionsInterface
{
    /**
     * @var int
     */
    private $_tabIndex = 0;
    /**
     * @var UserPreferences
     */
    private $_userPrefs;
    /**
     * @var int
     */
    private $_userId;


    /**
     * Constructor
     *
     * @param $template \SP\Core\Template con instancia de plantilla
     */
    public function __construct(\SP\Core\Template $template = null)
    {
        parent::__construct($template);


        $this->view->assign('tabs', array());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->_userId = Session::getUserId();
        $this->_userPrefs = UserPreferences::getPreferences($this->_userId);
    }

    /**
     * Obtener la pestaña de seguridad
     */
    public function getSecurityTab()
    {
        $this->setAction(self::ACTION_USR_PREFERENCES_SECURITY);

        $this->view->addTemplate('security');


        $twoFa = new Auth2FA($this->_userId, Session::getUserLogin());

        if (!$this->_userPrefs->isUse2Fa()) {
            $this->view->assign('qrCode', $twoFa->getUserQRCode());
        }

        $this->view->assign('userId', $this->_userId);
        $this->view->assign('chk2FAEnabled', $this->_userPrefs->isUse2Fa());

        $this->view->append('tabs', array('title' => _('Seguridad')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'security');
        $this->view->assign('actionId', $this->getAction(), 'security');
    }

    /**
     * Obtener el índice actual de las pestañas
     *
     * @return int
     */
    private function getTabIndex()
    {
        $index = $this->_tabIndex;
        $this->_tabIndex++;

        return $index;
    }

    /**
     * Obtener la pestaña de preferencias
     */
    public function getPreferencesTab()
    {
        $this->setAction(self::ACTION_USR_PREFERENCES_GENERAL);

        $this->view->addTemplate('preferences');

        $this->view->assign('userId', $this->_userId);
        $this->view->assign('langsAvailable', Language::getAvailableLanguages());
        $this->view->assign('currentLang', $this->_userPrefs->getLang());
        $this->view->assign('themesAvailable', Themes::getThemesAvailable());
        $this->view->assign('currentTheme', ($this->_userPrefs->getTheme()) ? $this->_userPrefs->getTheme() : \SP\Config\Config::getValue('sitetheme'));
        $this->view->assign('chkAccountLink', ($this->_userPrefs->isAccountLink()) ? 'checked="checked"' : '');
        $this->view->assign('resultsPerPage', ($this->_userPrefs->getResultsPerPage()) ? $this->_userPrefs->getResultsPerPage() : \SP\Config\Config::getValue('account_count'));
        $this->view->assign('chkSortViews', ($this->_userPrefs->isSortViews()) ? 'checked="checked"' : '');
        $this->view->assign('chkTopNavbar', ($this->_userPrefs->isTopNavbar()) ? 'checked="checked"' : '');
        $this->view->assign('chkOptionalActions', ($this->_userPrefs->isOptionalActions()) ? 'checked="checked"' : '');

        $this->view->append('tabs', array('title' => _('Preferencias')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'preferences');
        $this->view->assign('actionId', $this->getAction(), 'preferences');
    }
}