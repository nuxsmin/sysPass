<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Services;

use Psr\Container\ContainerInterface;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;

/**
 * Class Service
 *
 * @package SP\Services
 */
abstract class Service
{
    const STATUS_INTERNAL_ERROR = 1000;

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var ContainerInterface
     */
    protected $dic;

    /**
     * Service constructor.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    final public function __construct()
    {
        $this->dic = Bootstrap::getContainer();

        $this->config = $this->dic->get(Config::class);
        $this->session = $this->dic->get(Session::class);
        $this->eventDispatcher = $this->dic->get(EventDispatcher::class);

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}