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

namespace SP\Modules\Web\Controllers;

use Exception;
use SebastianBergmann\RecursionContext\Context;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Domain\Auth\Providers\Browser\BrowserAuthService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Bootstrap\RouteContextData;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\TemplateInterface;

use function SP\logger;
use function SP\processException;

/**
 * Clase base para los controladores
 */
abstract class ControllerBase
{
    use WebControllerTrait;

    protected const ERR_UNAVAILABLE = 0;

    protected readonly EventDispatcherInterface    $eventDispatcher;
    protected readonly ConfigFileService           $config;
    protected readonly Context|SessionContext      $session;
    protected readonly ThemeInterface              $theme;
    protected readonly AclInterface                $acl;
    protected readonly ConfigDataInterface         $configData;
    protected readonly RequestService              $request;
    protected readonly PhpExtensionCheckerService  $extensionChecker;
    protected readonly TemplateInterface           $view;
    protected readonly Helpers\LayoutHelper        $layoutHelper;
    protected readonly UriContextInterface         $uriContext;
    protected ?UserDto                             $userDto         = null;
    protected ?ProfileData                         $userProfileData = null;
    protected readonly bool                        $isAjax;
    protected readonly RouteContextData            $routeContextData;
    protected readonly Helpers\JsonResponseHandler $jsonResponse;
    private readonly BrowserAuthService            $browser;

    public function __construct(Application $application, WebControllerHelper $webControllerHelper)
    {
        $this->routeContextData = $webControllerHelper->getRouteContextData();
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
        $this->uriContext = $webControllerHelper->getUriContext();
        $this->jsonResponse = $webControllerHelper->getJsonResponseHandler();

        $this->isAjax = $this->request->isAjax();

        $loggedIn = $this->session->isLoggedIn();

        if ($loggedIn) {
            $this->userDto = clone $this->session->getUserData();
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
        $this->view->assign('themeUri', $this->theme->getUri());
        $this->view->assign('configData', $this->configData);
        $this->view->assign('action', $this->routeContextData->actionName);

        if ($loggedIn) {
            $this->view->assignWithScope('userId', $this->userDto->id, 'ctx');
            $this->view->assignWithScope('userGroupId', $this->userDto->userGroupId, 'ctx');
            $this->view->assignWithScope('userIsAdminApp', $this->userDto->isAdminApp, 'ctx');
            $this->view->assignWithScope('userIsAdminAcc', $this->userDto->isAdminAcc, 'ctx');
        }
    }

    /**
     * Mostrar los datos de la plantilla
     */
    protected function view(): void
    {
        $this->router->response()->body($this->view->render())->send();
    }

    /**
     * Renderizar los datos de la plantilla y devolverlos
     */
    protected function render(): string
    {
        return $this->view->render();
    }

    /**
     * Upgrades a View to use a full page layout
     */
    protected function upgradeView(?string $page = null): void
    {
        $this->view->upgrade();
        $this->view->assign('contentPage', $page ?: strtolower($this->routeContextData->actionName));

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
            && $this->browser->checkServerAuthUser($this->userDto->login) === false
            && $this->browser->checkServerAuthUser($this->userDto->ssoLogin) === false
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
        return $this->userDto->isAdminApp || $this->acl->checkUserAccess($action);
    }
}
