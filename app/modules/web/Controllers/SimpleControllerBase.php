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

namespace SP\Modules\Web\Controllers;

use DI\Container;
use Interop\Container\ContainerInterface;
use Klein\Klein;
use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
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
     * @var  Session
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
     * SimpleControllerBase constructor.
     *
     * @param Container $container
     * @param           $actionName
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(Container $container, $actionName)
    {
        $this->dic = $container;

        $this->controllerName = $this->getControllerName();
        $this->actionName = $actionName;

        $this->config = $this->dic->get(Config::class);
        $this->session = $this->dic->get(Session::class);
        $this->theme = $this->dic->get(Theme::class);
        $this->eventDispatcher = $this->dic->get(EventDispatcher::class);
        $this->router = $this->dic->get(Klein::class);

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Comprobaciones
     */
    protected function checks()
    {
        $this->checkLoggedInSession($this->session);
        $this->checkSecurityToken($this->session);
    }
}