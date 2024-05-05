<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Language;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Crypt\CryptPKIInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Providers\Uri;
use SP\Domain\Install\Services\Installer;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Infrastructure\File\FileSystem;
use SP\Mvc\View\TemplateInterface;
use SP\Plugin\PluginManager;

use function SP\__;
use function SP\processException;

/**
 * Class LayoutHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class LayoutHelper extends HelperBase
{
    private ThemeInterface    $theme;
    private CryptPKIInterface $cryptPKI;
    private bool              $loggedIn;

    public function __construct(
        Application                          $application,
        TemplateInterface                    $template,
        RequestService $request,
        ThemeInterface                       $theme,
        CryptPKIInterface                    $cryptPKI,
        private readonly UriContextInterface $uriContext,
        private readonly AclInterface        $acl
    ) {
        parent::__construct($application, $template, $request);

        $this->theme = $theme;
        $this->cryptPKI = $cryptPKI;
        $this->loggedIn = $this->context->isLoggedIn();

        $this->view->assign('loggedIn', $this->loggedIn);
    }

    /**
     * Sets a full layout page
     *
     * @param string $page Page/view name
     * @param AclInterface|null $acl
     *
     * @return LayoutHelper
     */
    public function getFullLayout(string $page, AclInterface $acl = null): LayoutHelper
    {
        $this->view->addTemplate('main', '_layouts');
        $this->view->assign('useFixedHeader', true);

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
     */
    public function setPage(string $page): void
    {
        $this->view->assign('page', $page);
    }

    /**
     * Inicializar las variables para la vista principal de la aplicación
     */
    public function initBody(): void
    {
        $baseUrl = $this->configData->getApplicationUrl() ?? $this->uriContext->getWebUri();

        $this->view->assign('isInstalled', $this->configData->isInstalled());
        $this->view->assign('app_name', AppInfoInterface::APP_NAME);
        $this->view->assign('app_desc', AppInfoInterface::APP_DESC);
        $this->view->assign('app_website_url', AppInfoInterface::APP_WEBSITE_URL);
        $this->view->assign('app_blog_url', AppInfoInterface::APP_BLOG_URL);
        $this->view->assign('app_version', Installer::VERSION_TEXT);
        $this->view->assign('logo_icon', $baseUrl . '/public/images/logo_icon.png');
        $this->view->assign('logo_no_bg_color', $baseUrl . '/public/images/logo_full_nobg_outline_color.png');
        $this->view->assign('logo_no_bg', $baseUrl . '/public/images/logo_full_nobg_outline.png');
        $this->view->assign('httpsEnabled', $this->request->isHttps());
        $this->view->assign('homeRoute', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT));

        $this->loggedIn = $this->context->isLoggedIn();

        $this->view->assign('loggedIn', $this->loggedIn);
        $this->view->assign('lang', $this->loggedIn ? Language::$userLang : substr(Language::$globalLang, 0, 2));
        $this->view->assign('loadApp', $this->context->getAuthCompleted());

        try {
            // Cargar la clave pública en la sesión
            $this->context->setPublicKey($this->cryptPKI->getPublicKey());
        } catch (SPException $e) {
            processException($e);
        }

        $this->getResourcesLinks();
        $this->setResponseHeaders();
    }

    /**
     * Obtener los datos para la cabcera de la página
     */
    protected function getResourcesLinks(): void
    {
        $version = Version::getVersionStringNormalized();
        $baseUrl = ($this->configData->getApplicationUrl() ?? $this->uriContext->getWebUri()) .
                   $this->uriContext->getSubUri();

        $jsUriApp = new Uri($baseUrl);
        $jsUriApp->addParams(['_r' => 'resource/js', '_v' => sha1($version)]);

        $this->view->append('jsLinks', $jsUriApp->getUriSigned($this->configData->getPasswordSalt()));

        $jsUriVendor = new Uri($baseUrl);
        $jsUriVendor->addParams(['g' => 1]);

        $this->view->append('jsLinks', $jsUriVendor->getUriSigned($this->configData->getPasswordSalt()));

        $themeInfo = $this->theme->getInfo();

        if (isset($themeInfo['js'])) {
            $jsUriTheme = new Uri($baseUrl);
            $jsUriTheme->addParams(
                [
                    'b' => FileSystem::buildPath($this->theme->getPath(), 'js'),
                    'f' => implode(',', $themeInfo['js'])
                ]
            );

            $this->view->append('jsLinks', $jsUriTheme->getUriSigned($this->configData->getPasswordSalt()));
        }

        $userPreferences = $this->context->getUserData()->getPreferences();

        if ($this->loggedIn
            && $userPreferences
            && $userPreferences->getUserId() > 0
        ) {
            $resultsAsCards = $userPreferences->isResultsAsCards();
        } else {
            $resultsAsCards = $this->configData->isResultsAsCards();
        }

        $cssUriApp = new Uri($baseUrl);
        $cssUriApp->addParams(['_r' => 'resource/css', '_v' => sha1($version . $resultsAsCards)]);

        $this->view->append('cssLinks', $cssUriApp->getUriSigned($this->configData->getPasswordSalt()));

        if (isset($themeInfo['css'])) {
            $themeInfo['css'][] = $resultsAsCards
                ? 'search-card.min.css'
                : 'search-grid.min.css';

            if ($this->configData->isDokuwikiEnabled()) {
                $themeInfo['css'][] = 'styles-wiki.min.css';
            }

            $cssUriTheme = new Uri($baseUrl);
            $cssUriTheme->addParams(
                [
                    'b' => FileSystem::buildPath($this->theme->getPath(), 'css'),
                    'f' => implode(',', $themeInfo['css'])
                ]
            );

            $this->view->append('cssLinks', $cssUriTheme->getUriSigned($this->configData->getPasswordSalt()));
        }

        // Cargar los recursos de los plugins
        $loadedPlugins = $this->pluginManager->getLoadedPlugins();

        foreach ($loadedPlugins as $plugin) {
            $base = str_replace(APP_ROOT, '', $plugin->getBase());
            $base .= DIRECTORY_SEPARATOR . 'public';

            $jsResources = $plugin->getJsResources();
            $cssResources = $plugin->getCssResources();

            if (count($jsResources) > 0) {
                $jsUriPlugin = new Uri($baseUrl);
                $jsUriPlugin->addParams([
                                            'b' => FileSystem::buildPath($base, 'js'),
                                            'f' => implode(',', $jsResources)
                                        ]);

                $this->view->append('jsLinks', $jsUriPlugin->getUriSigned($this->configData->getPasswordSalt()));
            }

            if (count($cssResources) > 0) {
                $cssUriPlugin = new Uri($baseUrl);
                $cssUriPlugin->addParams(
                    [
                        'b' => FileSystem::buildPath($base, 'css'),
                        'f' => implode(',', $cssResources)
                    ]
                );

                $this->view->append('cssLinks', $cssUriPlugin->getUriSigned($this->configData->getPasswordSalt()));
            }
        }
    }

    /**
     * Establecer las cabeceras HTTP
     */
    private function setResponseHeaders(): void
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
    public function getSessionBar(): void
    {
        $userType = null;

        $userData = $this->context->getUserData();
        $icons = $this->theme->getIcons();

        if ($userData->getIsAdminApp()) {
            $userType = $icons->appAdmin();
        } elseif ($userData->getIsAdminAcc()) {
            $userType = $icons->accAdmin();
        }

        $this->view->assign('ctx_userType', $userType);
        $this->view->assign('ctx_userLogin', mb_strtoupper($userData->getLogin()));
        $this->view->assign('ctx_userName', $userData->getName() ?: mb_strtoupper($userData->getLogin()));
        $this->view->assign('ctx_userGroup', $userData->getUserGroupName());
        $this->view->assign('showPassIcon', !($this->configData->isLdapEnabled() && $userData->getIsLdap()));
    }

    /**
     * Obtener los datos para mostrar el menú de acciones
     *
     * @param AclInterface $acl
     */
    public function getMenu(AclInterface $acl): void
    {
        $icons = $this->theme->getIcons();
        $actions = [];

        $actionSearch = new DataGridAction();
        $actionSearch->setId(AclActionsInterface::ACCOUNT);
        $actionSearch->setTitle(__('Search'));
        $actionSearch->setIcon($icons->search());
        $actionSearch->setData([
                                   'historyReset' => 1,
                                   'view' => 'search',
                                   'route' => Acl::getActionRoute(AclActionsInterface::ACCOUNT),
                               ]);

        $actions[] = $actionSearch;

        if ($acl->checkUserAccess(AclActionsInterface::ACCOUNT_CREATE)) {
            $actionNewAccount = new DataGridAction();
            $actionNewAccount->setId(AclActionsInterface::ACCOUNT_CREATE);
            $actionNewAccount->setTitle(__('New Account'));
            $actionNewAccount->setIcon($icons->add());
            $actionNewAccount->setData([
                                           'historyReset' => 0,
                                           'view' => 'account',
                                           'route' => Acl::getActionRoute(AclActionsInterface::ACCOUNT_CREATE),
                                       ]);

            $actions[] = $actionNewAccount;
        }

        if ($acl->checkUserAccess(AclActionsInterface::ACCESS_MANAGE)) {
            $actionAccessManager = new DataGridAction();
            $actionAccessManager->setId(AclActionsInterface::ACCESS_MANAGE);
            $actionAccessManager->setTitle(Acl::getActionInfo(AclActionsInterface::ACCESS_MANAGE));
            $actionAccessManager->setIcon($icons->account());
            $actionAccessManager->setData([
                                              'historyReset' => 0,
                                              'view' => 'datatabs',
                                              'route' => Acl::getActionRoute(AclActionsInterface::ACCESS_MANAGE),
                                          ]);

            $actions[] = $actionAccessManager;
        }

        if ($acl->checkUserAccess(AclActionsInterface::ITEMS_MANAGE)) {
            $actionItemManager = new DataGridAction();
            $actionItemManager->setId(AclActionsInterface::ITEMS_MANAGE);
            $actionItemManager->setTitle(Acl::getActionInfo(AclActionsInterface::ITEMS_MANAGE));
            $actionItemManager->setIcon($icons->group());
            $actionItemManager->setData([
                                            'historyReset' => 0,
                                            'view' => 'datatabs',
                                            'route' => Acl::getActionRoute(AclActionsInterface::ITEMS_MANAGE),
                                        ]);

            $actions[] = $actionItemManager;
        }

        if ($acl->checkUserAccess(AclActionsInterface::SECURITY_MANAGE)) {
            $actionSecurityManager = new DataGridAction();
            $actionSecurityManager->setId(AclActionsInterface::SECURITY_MANAGE);
            $actionSecurityManager->setTitle(Acl::getActionInfo(AclActionsInterface::SECURITY_MANAGE));
            $actionSecurityManager->setIcon($icons->getIconByName('security'));
            $actionSecurityManager->setData([
                                                'historyReset' => 0,
                                                'view' => 'datatabs',
                                                'route' => Acl::getActionRoute(AclActionsInterface::SECURITY_MANAGE),
                                            ]);

            $actions[] = $actionSecurityManager;
        }

        if ($acl->checkUserAccess(AclActionsInterface::PLUGIN)) {
            $actionPlugins = new DataGridAction();
            $actionPlugins->setId(AclActionsInterface::PLUGIN);
            $actionPlugins->setTitle(__('Plugins'));
            $actionPlugins->setIcon($icons->getIconByName('extension'));
            $actionPlugins->setData([
                                        'historyReset' => 1,
                                        'view' => 'plugin',
                                        'route' => Acl::getActionRoute(AclActionsInterface::PLUGIN),
                                    ]);

            $actions[] = $actionPlugins;
        }

        if ($acl->checkUserAccess(AclActionsInterface::CONFIG)) {
            $actionConfigManager = new DataGridAction();
            $actionConfigManager->setId('config');
            $actionConfigManager->setTitle(__('Configuration'));
            $actionConfigManager->setIcon($icons->settings());
            $actionConfigManager->setData([
                                              'historyReset' => 1,
                                              'view' => 'config',
                                              'route' => Acl::getActionRoute(AclActionsInterface::CONFIG),
                                          ]);

            $actions[] = $actionConfigManager;
        }

        $this->view->assign('actions', $actions);
        $this->view->assign('useMenu', true);
    }

    /**
     * Sets a full layout page
     *
     * @param string $template
     * @param string $page Page/view name
     *
     * @return LayoutHelper
     */
    public function getPublicLayout(string $template, string $page = ''): LayoutHelper
    {
        $this->view->setLayout('main');
        $this->view->addContentTemplate($template);
        $this->view->assign('useFixedHeader', true);

        $this->setPage($page);
        $this->initBody();

        return $this;
    }

    /**
     * Sets a custom layout page
     *
     * @param string $template
     * @param string $page Page/view name
     *
     * @return LayoutHelper
     */
    public function getCustomLayout(string $template, string $page = ''): LayoutHelper
    {
        $this->view->setLayout('main');
        $this->view->addContentTemplate($template);

        $this->setPage($page);
        $this->initBody();

        return $this;
    }
}
