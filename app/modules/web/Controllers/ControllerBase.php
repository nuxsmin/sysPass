<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

defined('APP_ROOT') || die();

use Exception;
use Klein\Klein;
use Psr\Container\ContainerInterface;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Config\ConfigDataInterface;
use SP\Core\Acl\Acl;
use SP\Core\Context\ContextInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\DataModel\ProfileData;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Browser\Browser;
use SP\Services\Auth\AuthException;
use SP\Services\User\UserLoginResponse;

/**
 * Clase base para los controladores
 */
abstract class ControllerBase
{
    use WebControllerTrait;

    protected const ERR_UNAVAILABLE = 0;

    // TODO: remove when controllers are ready
    protected ContainerInterface $dic;

    protected EventDispatcher     $eventDispatcher;
    protected Config              $config;
    protected ContextInterface    $session;
    protected ThemeInterface      $theme;
    protected Klein               $router;
    protected Acl                 $acl;
    protected ConfigDataInterface $configData;
    protected Request             $request;
    protected PhpExtensionChecker $extensionChecker;
    protected Template            $view;
    protected ?string             $actionName      = null;
    protected ?UserLoginResponse  $userData        = null;
    protected ?ProfileData        $userProfileData = null;
    protected bool                $isAjax;
    protected LayoutHelper        $layoutHelper;
    private Browser               $browser;

    /**
     * @throws \SP\Core\Exceptions\SessionTimeout
     * @throws \JsonException
     */
    public function __construct(
        EventDispatcher $eventDispatcher,
        Config $config,
        ContextInterface $session,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        Request $request,
        PhpExtensionChecker $extensionChecker,
        Template $template,
        Browser $browser,
        LayoutHelper $layoutHelper
    ) {
        // TODO: remove when controllers are ready
        $this->dic = Bootstrap::getContainer();

        $this->controllerName = $this->getControllerName();
        $this->configData = $config->getConfigData();
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $config;
        $this->session = $session;
        $this->theme = $theme;
        $this->router = $router;
        $this->acl = $acl;
        $this->request = $request;
        $this->extensionChecker = $extensionChecker;
        $this->browser = $browser;
        $this->layoutHelper = $layoutHelper;

        $this->view = $template;
        $this->view->setBase(strtolower($this->controllerName));
        $this->isAjax = $this->request->isAjax();

        $loggedIn = $this->session->isLoggedIn();

        if ($loggedIn) {
            $this->userData = clone $this->session->getUserData();
            $this->userProfileData = clone $this->session->getUserProfile();
        }

        $this->setViewVars($loggedIn);

        $this->setup = true;

        // TODO: call handleSessionTimeout from controller::initialize directly
        try {
            if (method_exists($this, 'initialize')) {
                $this->initialize();
            }
        } catch (SessionTimeout $sessionTimeout) {
            $this->handleSessionTimeout(
                function () {
                    return true;
                }
            );

            throw $sessionTimeout;
        }
    }

    /**
     * Set view variables
     */
    private function setViewVars(bool $loggedIn = false): void
    {
        $this->view->assign(
            'timeStart',
            $this->request->getServer('REQUEST_TIME_FLOAT')
        );
        $this->view->assign('queryTimeStart', microtime());

        if ($loggedIn) {
            $this->view->assign('ctx_userId', $this->userData->getId());
            $this->view->assign(
                'ctx_userGroupId',
                $this->userData->getUserGroupId()
            );
            $this->view->assign(
                'ctx_userIsAdminApp',
                $this->userData->getIsAdminApp()
            );
            $this->view->assign(
                'ctx_userIsAdminAcc',
                $this->userData->getIsAdminAcc()
            );
        }

        $this->view->assign('isDemo', $this->configData->isDemoEnabled());
        $this->view->assign(
            'themeUri',
            $this->view->getTheme()->getThemeUri()
        );
        $this->view->assign('configData', $this->configData);

        // Pass the action name to the template as a variable
        $this->view->assign('action', true);
    }

    /**
     * Mostrar los datos de la plantilla
     */
    protected function view(): void
    {
        try {
            $this->router->response()
                ->body($this->view->render())
                ->send();
        } catch (FileNotFoundException $e) {
            processException($e);

            $this->router->response()
                ->body(__($e->getMessage()))
                ->send(true);
        }
    }

    /**
     * Renderizar los datos de la plantilla y devolverlos
     */
    protected function render(): string
    {
        try {
            return $this->view->render();
        } catch (FileNotFoundException $e) {
            processException($e);

            return $e->getMessage();
        }
    }

    /**
     * Upgrades a View to use a full page layout
     */
    protected function upgradeView(?string $page = null): void
    {
        $this->view->upgrade();

        if ($this->view->isUpgraded() === false) {
            return;
        }

        $this->view->assign(
            'contentPage',
            $page ?: strtolower($this->controllerName)
        );

        try {
            $this->layoutHelper->getFullLayout('main', $this->acl);
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * Obtener los datos para la vista de depuración
     */
    protected function getDebug(): void
    {
        global $memInit;

        $this->view->addTemplate('debug', 'common');

        $this->view->assign(
            'time',
            getElapsedTime($this->router->request()->server()->get('REQUEST_TIME_FLOAT'))
        );
        $this->view->assign('memInit', $memInit / 1000);
        $this->view->assign('memEnd', memory_get_usage() / 1000);
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @param  bool  $requireAuthCompleted
     *
     * @throws \SP\Core\Exceptions\SessionTimeout
     * @throws \SP\Services\Auth\AuthException
     */
    protected function checkLoggedIn(bool $requireAuthCompleted = true): void
    {
        if ($this->session->isLoggedIn() === false
            || $this->session->getAuthCompleted() !== $requireAuthCompleted
        ) {
            throw new SessionTimeout();
        }

        // Comprobar si se ha identificado mediante el servidor web y el usuario coincide
        if ($this->session->isLoggedIn()
            && $this->session->getAuthCompleted() === $requireAuthCompleted
            && $this->configData->isAuthBasicEnabled()
            && $this->browser->checkServerAuthUser($this->userData->getLogin()) === false
            && $this->browser->checkServerAuthUser($this->userData->getSsoLogin()) === false
        ) {
            throw new AuthException('Invalid browser auth');
        }
    }

    /**
     * prepareSignedUriOnView
     *
     * Prepares view's variables to pass in a signed URI
     */
    final protected function prepareSignedUriOnView(): void
    {
        $from = $this->request->analyzeString('from');

        if ($from) {
            try {
                $this->request->verifySignature($this->configData->getPasswordSalt());

                $this->view->assign('from', $from);
                $this->view->assign(
                    'from_hash',
                    Hash::signMessage($from, $this->configData->getPasswordSalt())
                );
            } catch (SPException $e) {
                processException($e);
            }
        }
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param  int  $action  La acción a comprobar
     */
    protected function checkAccess(int $action): bool
    {
        return $this->userData->getIsAdminApp()
               || $this->acl->checkUserAccess($action);
    }
}