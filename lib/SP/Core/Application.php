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


use SP\Config\Config;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;

/**
 * The Application helper class. It holds all the needed dependencies for the application
 */
final class Application
{
    private Config           $config;
    private EventDispatcher  $eventDispatcher;
    private ContextInterface $context;

    /**
     * Module constructor.
     *
     * @param  \SP\Config\Config  $config
     * @param  \SP\Core\Events\EventDispatcher  $eventDispatcher
     * @param  \SP\Core\Context\ContextInterface  $context
     */
    public function __construct(
        Config $config,
        EventDispatcher $eventDispatcher,
        ContextInterface $context
    ) {
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->context = $context;
    }

    /**
     * @return \SP\Config\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return \SP\Core\Events\EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @return \SP\Core\Context\ContextInterface
     */
    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}