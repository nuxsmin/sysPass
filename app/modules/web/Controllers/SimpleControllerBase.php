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

namespace SP\Modules\Web\Controllers;

use DI\Container;
use Interop\Container\ContainerInterface;
use Klein\Klein;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Events\EventDispatcher;
use SP\Core\UI\Theme;
use SP\Mvc\Controller\ControllerTrait;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
    use ControllerTrait;

    /**
     * @var string Nombre del controlador
     */
    protected $controllerName;
    /**
     * @var  EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var  Config
     */
    protected $config;
    /**
     * @var  SessionContext
     */
    protected $session;
    /**
     * @var  Theme
     */
    protected $theme;
    /**
     * @var string
     */
    protected $actionName;
    /**
     * @var Klein
     */
    protected $router;
    /**
     * @var ContainerInterface
     */
    protected $dic;
    /**
     * @var Acl
     */
    protected $acl;
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * SimpleControllerBase constructor.
     *
     * @param Container $container
     * @param           $actionName
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(Container $container, $actionName)
    {
        $this->dic = $container;

        $this->controllerName = $this->getControllerName();
        $this->actionName = $actionName;

        $this->config = $this->dic->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->session = $this->dic->get(ContextInterface::class);
        $this->theme = $this->dic->get(Theme::class);
        $this->eventDispatcher = $this->dic->get(EventDispatcher::class);
        $this->router = $this->dic->get(Klein::class);
        $this->acl = $this->dic->get(Acl::class);

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Comprobaciones
     */
    protected function checks()
    {
        $this->checkLoggedInSession($this->session, $this->router);
        $this->checkSecurityToken($this->session);
    }

    /**
     * Comprobar si está permitido el acceso al módulo/página.
     *
     * @param null $action La acción a comprobar
     *
     * @throws UnauthorizedPageException
     */
    protected function checkAccess($action)
    {
        if (!$this->session->getUserData()->getIsAdminApp() && !$this->acl->checkUserAccess($action)) {
            throw new UnauthorizedPageException(UnauthorizedPageException::INFO);
        }
    }
}