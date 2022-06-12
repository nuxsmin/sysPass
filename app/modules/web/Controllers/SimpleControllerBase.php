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

use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Http\Request;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
    use WebControllerTrait;

    protected EventDispatcher     $eventDispatcher;
    protected ConfigFileService   $config;
    protected ContextInterface    $session;
    protected ThemeInterface      $theme;
    protected Klein               $router;
    protected Acl                 $acl;
    protected Request             $request;
    protected PhpExtensionChecker $extensionChecker;
    protected ConfigDataInterface $configData;

    /**
     * @throws \SP\Core\Exceptions\SessionTimeout
     * @throws \JsonException
     */
    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker
    ) {
        $this->theme = $theme;
        $this->router = $router;
        $this->acl = $acl;
        $this->request = $request;
        $this->extensionChecker = $extensionChecker;
        $this->controllerName = $this->getControllerName();
        $this->config = $application->getConfig();
        $this->configData = $this->config->getConfigData();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->session = $application->getContext();
        $this->setup = true;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Comprobaciones
     *
     * @throws SessionTimeout
     * @throws \JsonException
     */
    protected function checks(): void
    {
        if ($this->session->isLoggedIn() === false || $this->session->getAuthCompleted() !== true) {
            $this->handleSessionTimeout();

            throw new SessionTimeout();
        }

//        $this->checkSecurityToken($this->session, $this->request);
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @throws UnauthorizedPageException
     */
    protected function checkAccess(int $action): void
    {
        if (!$this->acl->checkUserAccess($action) && !$this->session->getUserData()->getIsAdminApp()) {
            throw new UnauthorizedPageException(SPException::INFO);
        }
    }
}