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

use Interop\Container\ContainerInterface;
use Klein\Klein;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\UI\Theme;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
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
     * @param $actionName
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct($actionName)
    {
        $this->dic = Bootstrap::getContainer();

        $class = static::class;
        $this->controllerName = substr($class, strrpos($class, '\\') + 1, -strlen('Controller'));
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
     * Comprobar si la sesión está activa
     */
    protected function checkSession()
    {
        if (!$this->session->isLoggedIn()) {
            if (Checks::isJson()) {
                $JsonResponse = new JsonResponse();
                $JsonResponse->setDescription(__u('La sesión no se ha iniciado o ha caducado'));
                $JsonResponse->setStatus(10);
                Json::returnJson($JsonResponse);
            } else {
                Util::logout();
            }
        }
    }

    /**
     * Comprobaciones
     */
    protected function checks()
    {
        $this->checkSession();
        $this->preActionChecks();
    }

    /**
     * Comprobaciones antes de realizar una acción
     */
    protected function preActionChecks()
    {
        $sk = Request::analyze('sk');

        if (!$sk || (null !== $this->session->getSecurityKey() && $this->session->getSecurityKey() === $sk)) {
            $this->invalidAction();
        }
    }

    /**
     * Acción no disponible
     */
    protected function invalidAction()
    {
        Json::returnJson((new JsonResponse())->setDescription(__u('Acción Inválida')));
    }
}