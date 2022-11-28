<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;


use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Config\Ports\ConfigInterface;

/**
 * The Application helper class. It holds all the needed dependencies for the application
 */
final class Application
{
    private ConfigInterface  $config;
    private EventDispatcher  $eventDispatcher;
    private ContextInterface $context;

    /**
     * Module constructor.
     *
     * @param  ConfigInterface  $config
     * @param  EventDispatcher  $eventDispatcher
     * @param  ContextInterface  $context
     */
    public function __construct(
        ConfigInterface $config,
        EventDispatcher $eventDispatcher,
        ContextInterface $context
    ) {
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->context = $context;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}