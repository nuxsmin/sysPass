<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Controller;

defined('APP_ROOT') || die();

use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\DiFactory;
use SP\Core\Language;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\DataModel\UserPreferencesData;
use SP\Mgmt\Users\UserPreferences;

/**
 * Class UsersPrefs encargada de mostrar las preferencias de los usuarios
 *
 * @package SP\Controller
 */
class UserPreferencesController extends TabControllerBase implements ActionsInterface
{
    /**
     * @var UserPreferencesData
     */
    private $userPrefs;
    /**
     * @var int
     */
    private $userId;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('tabs', []);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->userId = $this->UserData->getUserId();
        $this->userPrefs = UserPreferences::getItem()->getById($this->userId);
    }

    /**
     * Obtener la pestaña de preferencias
     */
    public function getPreferencesTab()
    {
        $this->setAction(self::ACTION_USR_PREFERENCES_GENERAL);

        $this->view->addTemplate('preferences-site');

        $this->view->assign('userId', $this->userId);
        $this->view->assign('langsAvailable', Language::getAvailableLanguages());
        $this->view->assign('currentLang', $this->userPrefs->getLang());
        $this->view->assign('themesAvailable', DiFactory::getTheme()->getThemesAvailable());
        $this->view->assign('currentTheme', $this->userPrefs->getTheme() ?: Config::getConfig()->getSiteTheme());
        $this->view->assign('chkAccountLink', $this->userPrefs->isAccountLink() ? 'checked="checked"' : '');
        $this->view->assign('resultsPerPage', $this->userPrefs->getResultsPerPage() ?: Config::getConfig()->getAccountCount());
        $this->view->assign('chkSortViews', $this->userPrefs->isSortViews() ? 'checked="checked"' : '');
        $this->view->assign('chkTopNavbar', $this->userPrefs->isTopNavbar() ? 'checked="checked"' : '');
        $this->view->assign('chkOptionalActions', $this->userPrefs->isOptionalActions() ? 'checked="checked"' : '');
        $this->view->assign('chkResultsAsCards', $this->userPrefs->isResultsAsCards() ? 'checked="checked"' : '');

        $this->view->assign('tabIndex', $this->addTab(__('Preferencias')), 'preferences');
        $this->view->assign('actionId', $this->getAction(), 'preferences');
    }

    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        $this->view->addTemplate('tabs-start', 'common');

        $this->getPreferencesTab();

        $this->EventDispatcher->notifyEvent('user.preferences', $this);

        $this->view->addTemplate('tabs-end', 'common');
    }
}