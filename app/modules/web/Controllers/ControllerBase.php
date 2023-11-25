<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Context\SessionContextInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\DataModel\ProfileData;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Domain\User\Services\UserLoginResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Auth\Browser\BrowserAuthInterface;

use function SP\__;
use function SP\logger;
use function SP\processException;

/**
 * Clase base para los controladores
 */
abstract class ControllerBase
{
    use WebControllerTrait;

    protected const ERR_UNAVAILABLE = 0;

    protected EventDispatcher         $eventDispatcher;
    protected ConfigFileService       $config;
    protected SessionContextInterface $session;
    protected ThemeInterface          $theme;
    protected Acl                     $acl;
    protected ConfigDataInterface     $configData;
    protected RequestInterface        $request;
    protected PhpExtensionChecker     $extensionChecker;
    protected TemplateInterface       $view;
    protected ?UserLoginResponse      $userData        = null;
    protected ?ProfileData            $userProfileData = null;
    protected bool                    $isAjax;
    protected LayoutHelper            $layoutHelper;
    protected string                  $actionName;
    private BrowserAuthInterface      $browser;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper
    ) {
        $this->controllerName = $this->getControllerName();
        $this->config = $application->getConfig();
        $this->configData = $this->config->getConfigData();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->session = $application->getContext();
        $this->theme = $webControllerHelper->getTheme();
        $this->router = $webControllerHelper->getRouter();
        $this->acl = $webControllerHelper->getAcl();
        $this->request = $webControllerHelper->getRequest();
        $this->extensionChecker = $webControllerHelper->getExtensionChecker();
        $this->browser = $webControllerHelper->getBrowser();
        $this->layoutHelper = $webControllerHelper->getLayoutHelper();
        $this->view = $webControllerHelper->getTemplate();

        $this->view->setBase($this->getViewBaseName());
        $this->isAjax = $this->request->isAjax();
        $this->actionName = $this->session->getTrasientKey(BootstrapBase::CONTEXT_ACTION_NAME);

        $loggedIn = $this->session->isLoggedIn();

        if ($loggedIn) {
            $this->userData = clone $this->session->getUserData();
            $this->userProfileData = clone $this->session->getUserProfile();
        }

        $this->setViewVars($loggedIn);

        $this->setup = true;

        logger(static::class);
    }

    /**
     * Set view variables
     */
    private function setViewVars(bool $loggedIn = false): void
    {
        $this->view->assign('timeStart', $this->request->getServer('REQUEST_TIME_FLOAT'));
        $this->view->assign('queryTimeStart', microtime());
        $this->view->assign('isDemo', $this->configData->isDemoEnabled());
        $this->view->assign('themeUri', $this->view->getTheme()->getUri());
        $this->view->assign('configData', $this->configData);

        if ($loggedIn) {
            $this->view->assign('ctx_userId', $this->userData->getId());
            $this->view->assign('ctx_userGroupId', $this->userData->getUserGroupId());
            $this->view->assign('ctx_userIsAdminApp', $this->userData->getIsAdminApp());
            $this->view->assign('ctx_userIsAdminAcc', $this->userData->getIsAdminAcc());
        }

        $this->view->assign('action', $this->actionName);
    }

    /**
     * Mostrar los datos de la plantilla
     */
    protected function view(): void
    {
        try {
            $this->router->response()->body($this->view->render())->send();
        } catch (FileNotFoundException $e) {
            processException($e);

            $this->router->response()->body(__($e->getMessage()))->send(true);
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

        $this->view->assign('contentPage', $page ?: strtolower($this->getViewBaseName()));

        try {
            $this->layoutHelper->getFullLayout('main', $this->acl);
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * Comprobar si el usuario está logado.
     *
     * @param bool $requireAuthCompleted
     *
     * @throws SessionTimeout
     * @throws AuthException
     */
    protected function checkLoggedIn(bool $requireAuthCompleted = true): void
    {
        if ($this->session->isLoggedIn() === false || $this->session->getAuthCompleted() !== $requireAuthCompleted
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
                $this->view->assign('from_hash', Hash::signMessage($from, $this->configData->getPasswordSalt()));
            } catch (SPException $e) {
                processException($e);
            }
        }
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param int $action La acción a comprobar
     */
    protected function checkAccess(int $action): bool
    {
        return $this->userData->getIsAdminApp() || $this->acl->checkUserAccess($action);
    }
}
