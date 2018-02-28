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

namespace SP\Providers;

use DI\Container;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;

/**
 * Class Service
 *
 * @package SP\Providers
 */
abstract class Provider
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
     * Provider constructor.
     *
     * @param Container       $dic
     * @param Config          $config
     * @param Session         $session
     * @param EventDispatcher $eventDispatcher
     */
    final public function __construct(Container $dic, Config $config, Session $session, EventDispatcher $eventDispatcher)
    {
        $this->dic = $dic;
        $this->config = $config;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}