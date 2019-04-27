<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;

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
     * @var ContextInterface
     */
    protected $context;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Provider constructor.
     *
     * @param ContainerInterface $dic
     */
    final public function __construct(ContainerInterface $dic)
    {
        $this->config = $dic->get(Config::class);
        $this->context = $dic->get(ContextInterface::class);
        $this->eventDispatcher = $dic->get(EventDispatcher::class);

        if (method_exists($this, 'initialize')) {
            $this->initialize($dic);
        }
    }
}