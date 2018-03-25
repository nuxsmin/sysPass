<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Crypt\CryptPKI;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\Plugin\PluginUtil;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeInterface;
use SP\Html\DataGrid\DataGridAction;
use SP\Services\Install\Installer;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Class LayoutHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class LayoutHelper extends HelperBase
{
    /**
     * @var  bool
     */
    protected $loggedIn;
    /**
     * @var ThemeInterface
     */
    protected $theme;

    /**
     * Sets a full layout page
     *
     * @param string $page Page/view name
     * @param Acl    $acl
     * @return LayoutHelper
     */
    public function getFullLayout($page, Acl $acl = null)
    {
        $this->view->addTemplate('main', '_layouts');
        $this->view->assign('useFixedHeader');

        $this->setPage($page);
        $this->initBody();

        if ($this->loggedIn) {
            $this->getSessionBar();
        }

        if ($acl !== null) {
            $this->getMenu($acl);
        }

        return $this;
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
     * Inicializar las variables para la vista principal de la aplicación
     */
    public function initBody()
    {
        $this->view->assign('startTime', microtime());

        $this->view->assign('isInstalled', $this->configData->isInstalled());
        $this->view->assign('sk', $this->loggedIn ? $this->context->generateSecurityKey() : '');
        $this->view->assign('appInfo', Util::getAppInfo());
        $this->view->assign('appVersion', Installer::VERSION_TEXT);
        $this->view->assign('isDemoMode', $this->configData->isDemoEnabled());
        $this->view->assign('icons', $this->theme->getIcons());
        $this->view->assign('logoIcon', Bootstrap::$WEBURI . '/public/images/logo_icon.png');
        $this->view->assign('logoNoText', Bootstrap::$WEBURI . '/public/images/logo_icon.svg');
        $this->view->assign('logo', Bootstrap::$WEBURI . '/public/images/logo_full_bg.png');
        $this->view->assign('logonobg', Bootstrap::$WEBURI . '/public/images/logo_full_nobg.png');
        $this->view->assign('httpsEnabled', Checks::httpsEnabled());
        $this->view->assign('homeRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT));

        $this->loggedIn = $this->context->isLoggedIn();

        $this->view->assign('loggedIn', $this->loggedIn);
        $this->view->assign('lang', $this->loggedIn ? Language::$userLang : substr(Language::$globalLang, 0, 2));
        $this->view->assign('loadApp', $this->context->getAuthCompleted());


        try {
            // Cargar la clave pública en la sesión
            $this->context->setPublicKey($this->dic->get(CryptPKI::class)->getPublicKey());
        } catch (SPException $e) {
            processException($e);
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

        $jsUri = Bootstrap::$WEBURI . '/index.php?r=resource/js';
        $jsVersionHash = md5($version);
        $this->view->append('jsLinks', $jsUri . '&v=' . $jsVersionHash);
        $this->view->append('jsLinks', $jsUri . '&g=1&v=' . $jsVersionHash);

        $themeInfo = $this->theme->getThemeInfo();

        if (isset($themeInfo['js'])) {
            $themeJsBase = urlencode($this->theme->getThemePath() . DIRECTORY_SEPARATOR . 'js');
            $themeJsFiles = urlencode(implode(',', $themeInfo['js']));

            $this->view->append('jsLinks', $jsUri . '&f=' . $themeJsFiles . '&b=' . $themeJsBase . '&v=' . $jsVersionHash);
        }

        $userPreferences = $this->context->getUserData()->getPreferences();

        if ($this->loggedIn && $userPreferences->getUserId() > 0) {
            $resultsAsCards = $userPreferences->isResultsAsCards();
        } else {
            $resultsAsCards = $this->configData->isResultsAsCards();
        }

        $cssUri = Bootstrap::$WEBURI . '/index.php?r=resource/css';
        $cssVersionHash = md5($version . $resultsAsCards);
        $this->view->append('cssLinks', $cssUri . '&v=' . $cssVersionHash);

        if (isset($themeInfo['css'])) {
            $themeInfo['css'][] = $resultsAsCards ? 'search-card.min.css' : 'search-grid.min.css';

            if ($this->configData->isDokuwikiEnabled()) {
                $themeInfo['css'][] = 'styles-wiki.min.css';
            }

            $themeCssBase = urlencode($this->theme->getThemePath() . DIRECTORY_SEPARATOR . 'css');
            $themeCssFiles = urlencode(implode(',', $themeInfo['css']));

            $this->view->append('cssLinks', $cssUri . '&f=' . $themeCssFiles . '&b=' . $themeCssBase . '&v=' . $jsVersionHash);
        }

        // Cargar los recursos de los plugins
        foreach (PluginUtil::getLoadedPlugins() as $Plugin) {
            $base = str_replace(BASE_PATH, '', $Plugin->getBase());
            $jsResources = $Plugin->getJsResources();
            $cssResources = $Plugin->getCssResources();

            if (count($jsResources) > 0) {
                $this->view->append('jsLinks', $jsUri . '&f=' . urlencode(implode(',', $jsResources)) . '&b=' . urlencode($base . DIRECTORY_SEPARATOR . 'js') . '&v=' . $jsVersionHash);
            }

            if (count($cssResources) > 0) {
                $this->view->append('cssLinks', $cssUri . '&f=' . urlencode(implode(',', $cssResources)) . '&b=' . urlencode($base . DIRECTORY_SEPARATOR . 'css') . '&v=' . $jsVersionHash);
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
     * Obtener los datos para la mostrar la barra de sesión
     */
    public function getSessionBar()
    {
        $userType = null;

        $userData = $this->context->getUserData();
        $icons = $this->theme->getIcons();

        if ($userData->getIsAdminApp()) {
            $userType = $icons->getIconAppAdmin();
        } elseif ($userData->getIsAdminAcc()) {
            $userType = $icons->getIconAccAdmin();
        }

        $this->view->assign('context_userType', $userType);
        $this->view->assign('context_userId', $userData->getId());
        $this->view->assign('context_userLogin', mb_strtoupper($userData->getLogin()));
        $this->view->assign('context_userName', $userData->getName() ?: mb_strtoupper($this->view->userLogin));
        $this->view->assign('context_userGroup', $userData->getUserGroupName());
        $this->view->assign('showPassIcon', !($this->configData->isLdapEnabled() && $userData->getIsLdap()));
        $this->view->assign('notifications', 0);
    }

    /**
     * Obtener los datos para mostrar el menú de acciones
     *
     * @param Acl $acl
     */
    public function getMenu(Acl $acl)
    {
        $icons = $this->theme->getIcons();

        $actionSearch = new DataGridAction();
        $actionSearch->setId(ActionsInterface::ACCOUNT);
        $actionSearch->setTitle(__('Buscar'));
        $actionSearch->setIcon($icons->getIconSearch());
        $actionSearch->setData([
            'historyReset' => 1,
            'view' => 'search',
            'route' => Acl::getActionRoute(ActionsInterface::ACCOUNT)
        ]);

        $this->view->append('actions', $actionSearch);

        if ($acl->checkUserAccess(ActionsInterface::ACCOUNT_CREATE)) {
            $actionNewAccount = new DataGridAction();
            $actionNewAccount->setId(ActionsInterface::ACCOUNT_CREATE);
            $actionNewAccount->setTitle(__('Nueva Cuenta'));
            $actionNewAccount->setIcon($icons->getIconAdd());
            $actionNewAccount->setData([
                'historyReset' => 0,
                'view' => 'account',
                'route' => Acl::getActionRoute(ActionsInterface::ACCOUNT_CREATE)
            ]);

            $this->view->append('actions', $actionNewAccount);
        }

        if ($acl->checkUserAccess(ActionsInterface::ACCESS_MANAGE)) {
            $actionAccessManager = new DataGridAction();
            $actionAccessManager->setId(ActionsInterface::ACCESS_MANAGE);
            $actionAccessManager->setTitle(__('Usuarios y Accesos'));
            $actionAccessManager->setIcon($icons->getIconAccount());
            $actionAccessManager->setData([
                'historyReset' => 0,
                'view' => 'datatabs',
                'route' => Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE)
            ]);

            $this->view->append('actions', $actionAccessManager);
        }

        if ($acl->checkUserAccess(ActionsInterface::ITEMS_MANAGE)) {
            $actionItemManager = new DataGridAction();
            $actionItemManager->setId(ActionsInterface::ITEMS_MANAGE);
            $actionItemManager->setTitle(__('Elementos y Personalización'));
            $actionItemManager->setIcon($icons->getIconGroup());
            $actionItemManager->setData([
                'historyReset' => 0,
                'view' => 'datatabs',
                'route' => Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE)
            ]);

            $this->view->append('actions', $actionItemManager);
        }

        if ($acl->checkUserAccess(ActionsInterface::CONFIG)) {
            $actionConfigManager = new DataGridAction();
            $actionConfigManager->setId('config');
            $actionConfigManager->setTitle(__('Configuración'));
            $actionConfigManager->setIcon($icons->getIconSettings());
            $actionConfigManager->setData([
                'historyReset' => 1,
                'view' => 'config',
                'route' => Acl::getActionRoute(ActionsInterface::CONFIG)
            ]);

            $this->view->append('actions', $actionConfigManager);
        }

        if ($acl->checkUserAccess(ActionsInterface::EVENTLOG) && $this->configData->isLogEnabled()) {
            $actionEventlog = new DataGridAction();
            $actionEventlog->setId(ActionsInterface::EVENTLOG);
            $actionEventlog->setTitle(__('Registro de Eventos'));
            $actionEventlog->setIcon($icons->getIconHeadline());
            $actionEventlog->setData([
                'historyReset' => 1,
                'view' => 'eventlog',
                'route' => Acl::getActionRoute(ActionsInterface::EVENTLOG)
            ]);

            $this->view->append('actions', $actionEventlog);
        }

        $this->view->assign('useMenu', true);
    }

    /**
     * Sets a full layout page
     *
     * @param string $template
     * @param string $page Page/view name
     * @return LayoutHelper
     */
    public function getPublicLayout($template, $page = '')
    {
        $this->view->addTemplate('main', '_layouts');
        $this->view->addContentTemplate($template);
        $this->view->assign('useFixedHeader');

        $this->setPage($page);
        $this->initBody();

        return $this;
    }

    /**
     * Sets a custom layout page
     *
     * @param string $template
     * @param string $page Page/view name
     * @return LayoutHelper
     */
    public function getCustomLayout($template, $page = '')
    {
        $this->view->addTemplate('main', '_layouts');
        $this->view->addContentTemplate($template);

        $this->setPage($page);
        $this->initBody();

        return $this;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->theme = $this->dic->get(Theme::class);

        $this->loggedIn = $this->context->isLoggedIn();

        $this->view->assign('loggedIn', $this->loggedIn);
    }
}