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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Bootstrap;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\Plugin\PluginUtil;
use SP\Core\SessionUtil;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeInterface;
use SP\Html\DataGrid\DataGridAction;
use SP\Mgmt\Notices\Notice;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Class LayoutHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class LayoutHelper extends HelperBase
{
    /** @var  bool */
    protected $loggedIn;
    /** @var  ThemeInterface */
    protected $theme;

    /**
     * @param Theme $theme
     */
    public function inject(Theme $theme)
    {
        $this->theme = $theme;
    }

    /**
     * Inicializar las variables para la vista principal de la aplicación
     */
    public function initBody()
    {
        $this->view->assign('startTime', microtime());

        $this->view->addPartial('header');
        $this->view->addPartial('body-start');

        $this->view->assign('useLayout', true);
        $this->view->assign('isInstalled', $this->configData->isInstalled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('appInfo', Util::getAppInfo());
        $this->view->assign('appVersion', Util::getVersionString());
        $this->view->assign('isDemoMode', $this->configData->isDemoEnabled());
        $this->view->assign('icons', $this->theme->getIcons());
        $this->view->assign('logoIcon', Bootstrap::$WEBURI . '/public/images/logo_icon.png');
        $this->view->assign('logoNoText', Bootstrap::$WEBURI . '/public/images/logo_icon.svg');
        $this->view->assign('logo', Bootstrap::$WEBURI . '/public/images/logo_full_bg.png');
        $this->view->assign('logonobg', Bootstrap::$WEBURI . '/public/images/logo_full_nobg.png');
        $this->view->assign('httpsEnabled', Checks::httpsEnabled());

        $this->loggedIn = $this->session->isLoggedIn();

        $this->view->assign('loggedIn', $this->loggedIn);
        $this->view->assign('lang', $this->loggedIn ? Language::$userLang : Language::$globalLang);
        $this->view->assign('loadApp', $this->session->getAuthCompleted());


        try {
            // Cargar la clave pública en la sesión
            SessionUtil::loadPublicKey();
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);
        }

        $this->getResourcesLinks();
        $this->setResponseHeaders();
    }

    /**
     * Obtener los datos para la cabcera de la página
     */
    protected function getResourcesLinks()
    {
        $version = Util::getVersionStringNormalized();

        $jsVersionHash = md5($version);
        $this->view->append('jsLinks', Bootstrap::$WEBROOT . '/public/js/js.php?v=' . $jsVersionHash);
        $this->view->append('jsLinks', Bootstrap::$WEBROOT . '/public/js/js.php?g=1&v=' . $jsVersionHash);

        $themeInfo = $this->theme->getThemeInfo();

        if (isset($themeInfo['js'])) {
            $themeJsBase = urlencode($this->theme->getThemePath() . DIRECTORY_SEPARATOR . 'js');
            $themeJsFiles = urlencode(implode(',', $themeInfo['js']));

            $this->view->append('jsLinks', Bootstrap::$WEBROOT . '/public/js/js.php?f=' . $themeJsFiles . '&b=' . $themeJsBase . '&v=' . $jsVersionHash);
        }

        if ($this->loggedIn && $this->session->getUserPreferences()->getUserId() > 0) {
            $resultsAsCards = $this->session->getUserPreferences()->isResultsAsCards();
        } else {
            $resultsAsCards = $this->configData->isResultsAsCards();
        }

        $cssVersionHash = md5($version . $resultsAsCards);
        $this->view->append('cssLinks', Bootstrap::$WEBROOT . '/public/css/css.php?v=' . $cssVersionHash);

        if (isset($themeInfo['css'])) {
            if ($resultsAsCards) {
                $themeInfo['css'][] = 'search-card.min.css';
            } else {
                $themeInfo['css'][] = 'search-grid.min.css';
            }

            if ($this->configData->isDokuwikiEnabled()) {
                $themeInfo['css'][] = 'styles-wiki.min.css';
            }

            $themeCssBase = urlencode($this->theme->getThemePath() . DIRECTORY_SEPARATOR . 'css');
            $themeCssFiles = urlencode(implode(',', $themeInfo['css']));

            $this->view->append('cssLinks', Bootstrap::$WEBROOT . '/public/css/css.php?f=' . $themeCssFiles . '&b=' . $themeCssBase . '&v=' . $jsVersionHash);
        }

        // Cargar los recursos de los plugins
        foreach (PluginUtil::getLoadedPlugins() as $Plugin) {
            $base = str_replace(BASE_PATH, '', $Plugin->getBase());
            $jsResources = $Plugin->getJsResources();
            $cssResources = $Plugin->getCssResources();

            if (count($jsResources) > 0) {
                $this->view->append('jsLinks', Bootstrap::$WEBROOT . '/public/js/js.php?f=' . urlencode(implode(',', $jsResources)) . '&b=' . urlencode($base . DIRECTORY_SEPARATOR . 'js') . '&v=' . $jsVersionHash);
            }

            if (count($cssResources) > 0) {
                $this->view->append('cssLinks', Bootstrap::$WEBROOT . '/public/css/css.php?f=' . urlencode(implode(',', $cssResources)) . '&b=' . urlencode($base . DIRECTORY_SEPARATOR . 'css') . '&v=' . $jsVersionHash);
            }
        }
    }

    /**
     * Establecer las cabeceras HTTP
     */
    private function setResponseHeaders()
    {
        // UTF8 Headers
        header('Content-Type: text/html; charset=UTF-8');

        // Cache Control
        header('Cache-Control: public, no-cache, max-age=0, must-revalidate');
        header('Pragma: public; max-age=0');
    }

    /**
     * Establecer la variable de página de la vista
     *
     * @param $page
     */
    public function setPage($page)
    {
        $this->view->assign('page', $page);
    }

    /**
     * Obtener los datos para la mostrar la barra de sesión
     */
    public function getSessionBar()
    {
        $this->view->addPartial('sessionbar');

        $userType = null;

        $userData = $this->session->getUserData();
        $icons = $this->theme->getIcons();

        if ($userData->isUserIsAdminApp()) {
            $userType = $icons->getIconAppAdmin();
        } elseif ($userData->isUserIsAdminAcc()) {
            $userType = $icons->getIconAccAdmin();
        }

        $this->view->assign('userType', $userType);
        $this->view->assign('userId', $userData->getUserId());
        $this->view->assign('userLogin', mb_strtoupper($userData->getUserLogin()));
        $this->view->assign('userName', $userData->getUserName() ?: mb_strtoupper($this->view->userLogin));
        $this->view->assign('userGroup', $userData->getUsergroupName());
        $this->view->assign('showPassIcon', !($this->configData->isLdapEnabled() && $userData->isUserIsLdap()));
        $this->view->assign('userNotices', count(Notice::getItem()->getAllActiveForUser()));
    }

    /**
     * Obtener los datos para mostrar el menú de acciones
     *
     * @param Acl $acl
     */
    public function getMenu(Acl $acl)
    {
        $this->view->addPartial('body-header-menu');

        $icons = $this->theme->getIcons();

        $ActionSearch = new DataGridAction();
        $ActionSearch->setId(ActionsInterface::ACCOUNT);
        $ActionSearch->setTitle(__('Buscar'));
        $ActionSearch->setIcon($icons->getIconSearch());
        $ActionSearch->setData([
            'historyReset' => 1,
            'view' => 'search',
            'route' => Acl::getActionRoute(ActionsInterface::ACCOUNT)
        ]);

        $this->view->append('actions', $ActionSearch);

        if ($acl->checkUserAccess(ActionsInterface::ACCOUNT_CREATE)) {
            $ActionNew = new DataGridAction();
            $ActionNew->setId(ActionsInterface::ACCOUNT_CREATE);
            $ActionNew->setTitle(__('Nueva Cuenta'));
            $ActionNew->setIcon($icons->getIconAdd());
            $ActionNew->setData([
                'historyReset' => 0,
                'view' => 'account',
                'route' => Acl::getActionRoute(ActionsInterface::ACCOUNT_CREATE)
            ]);

            $this->view->append('actions', $ActionNew);
        }

        if ($acl->checkUserAccess(ActionsInterface::ACCESS_MANAGE)) {
            $ActionUsr = new DataGridAction();
            $ActionUsr->setId(ActionsInterface::ACCESS_MANAGE);
            $ActionUsr->setTitle(__('Usuarios y Accesos'));
            $ActionUsr->setIcon($icons->getIconAccount());
            $ActionUsr->setData([
                'historyReset' => 0,
                'view' => 'datatabs',
                'route' => Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE)
            ]);

            $this->view->append('actions', $ActionUsr);
        }

        if ($acl->checkUserAccess(ActionsInterface::ITEMS_MANAGE)) {
            $ActionMgm = new DataGridAction();
            $ActionMgm->setId(ActionsInterface::ITEMS_MANAGE);
            $ActionMgm->setTitle(__('Elementos y Personalización'));
            $ActionMgm->setIcon($icons->getIconGroup());
            $ActionMgm->setData([
                'historyReset' => 0,
                'view' => 'datatabs',
                'route' => Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE)
            ]);

            $this->view->append('actions', $ActionMgm);
        }

        if ($acl->checkUserAccess(ActionsInterface::CONFIG)) {
            $ActionConfig = new DataGridAction();
            $ActionConfig->setId('config');
            $ActionConfig->setTitle(__('Configuración'));
            $ActionConfig->setIcon($icons->getIconSettings());
            $ActionConfig->setData([
                'historyReset' => 1,
                'view' => 'config',
                'route' => Acl::getActionRoute(ActionsInterface::CONFIG)
            ]);

            $this->view->append('actions', $ActionConfig);
        }

        if ($acl->checkUserAccess(ActionsInterface::EVENTLOG) && $this->configData->isLogEnabled()) {
            $ActionEventlog = new DataGridAction();
            $ActionEventlog->setId(ActionsInterface::EVENTLOG);
            $ActionEventlog->setTitle(__('Registro de Eventos'));
            $ActionEventlog->setIcon($icons->getIconHeadline());
            $ActionEventlog->setData([
                'historyReset' => 1,
                'view' => 'eventlog',
                'route' => Acl::getActionRoute(ActionsInterface::EVENTLOG)
            ]);

            $this->view->append('actions', $ActionEventlog);
        }
    }

    /**
     * @param bool $loggedIn
     */
    protected function setLoggedIn($loggedIn)
    {
        $this->loggedIn = (bool)$loggedIn;
        $this->view->assign('loggedIn', $this->loggedIn);
    }
}