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
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\WebControllerTrait;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
    use WebControllerTrait;

    // TODO: remove when controllers are ready
    protected ContainerInterface $dic;

    protected EventDispatcher     $eventDispatcher;
    protected Config              $config;
    protected ContextInterface    $session;
    protected ThemeInterface      $theme;
    protected Klein               $router;
    protected Acl                 $acl;
    protected Request             $request;
    protected PhpExtensionChecker $extensionChecker;

    /**
     * @throws \SP\Core\Exceptions\SessionTimeout
     * @throws \JsonException
     */
    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        Request $request,
        PhpExtensionChecker $extensionChecker
    ) {
        // TODO: remove when controllers are ready
        $this->dic = BootstrapBase::getContainer();

        $this->controllerName = $this->getControllerName();
        $this->config = $application->getConfig();
        $this->configData = $this->config->getConfigData();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->session = $application->getContext();
        $this->theme = $theme;
        $this->router = $router;
        $this->acl = $acl;
        $this->request = $request;
        $this->extensionChecker = $extensionChecker;

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
     * Comprobaciones
     *
     * @throws SessionTimeout
     */
    protected function checks(): void
    {
        if ($this->session->isLoggedIn() === false
            || $this->session->getAuthCompleted() !== true
        ) {
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
        if (!$this->acl->checkUserAccess($action)
            && !$this->session->getUserData()->getIsAdminApp()
        ) {
            throw new UnauthorizedPageException(SPException::INFO);
        }
    }
}