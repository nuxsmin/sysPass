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

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Events\EventDispatcher;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFile;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\Services\Request;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class SimpleControllerBase
 */
abstract class SimpleControllerBase
{
    use WebControllerTrait;

    protected readonly EventDispatcher     $eventDispatcher;
    protected readonly ConfigFile          $config;
    protected readonly Context             $session;
    protected readonly ThemeInterface      $theme;
    protected readonly Acl                 $acl;
    protected readonly Request             $request;
    protected readonly PhpExtensionChecker $extensionChecker;
    protected readonly ConfigDataInterface $configData;
    protected readonly UriContextInterface $uriContext;

    /**
     * @throws SessionTimeout
     */
    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper
    ) {
        $this->theme = $simpleControllerHelper->getTheme();
        $this->router = $simpleControllerHelper->getRouter();
        $this->acl = $simpleControllerHelper->getAcl();
        $this->request = $simpleControllerHelper->getRequest();
        $this->extensionChecker = $simpleControllerHelper->getExtensionChecker();
        $this->uriContext = $simpleControllerHelper->getUriContext();
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
     * @throws SPException
     * @throws SessionTimeout
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
