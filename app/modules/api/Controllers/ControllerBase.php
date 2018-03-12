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

namespace SP\Modules\Api\Controllers;

use DI\Container;
use SP\Core\Context\ApiContext;
use SP\Core\Events\EventDispatcher;

/**
 * Class ControllerBase
 * @package SP\Modules\Api\Controllers
 */
abstract class ControllerBase
{
    /**
     * @var Container
     */
    protected $dic;
    /**
     * @var string
     */
    protected $controllerName;
    /**
     * @var
     */
    protected $actionName;
    /**
     * @var ApiContext
     */
    protected $context;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * @param Container $container
     * @param           $actionName
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public final function __construct(Container $container, $actionName)
    {
        $this->dic = $container;
        $this->context = $container->get(ApiContext::class);
        $this->eventDispatcher = $container->get(EventDispatcher::class);

        $this->controllerName = $this->getControllerName();
        $this->actionName = $actionName;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * @return string
     */
    protected function getControllerName()
    {
        $class = static::class;

        return substr($class, strrpos($class, '\\') + 1, -strlen('Controller')) ?: '';
    }
}