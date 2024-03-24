<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Context\SessionContextInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;

/**
 * The Application helper class. It holds all the needed dependencies for the application
 */
final class Application
{
    /**
     * Module constructor.
     *
     * @param ConfigFileService $config
     * @param EventDispatcherInterface $eventDispatcher
     * @param SessionContextInterface|ContextInterface $context
     */
    public function __construct(
        private readonly ConfigFileService $config,
        private readonly EventDispatcherInterface                 $eventDispatcher,
        private readonly SessionContextInterface|ContextInterface $context
    ) {
    }

    public function getConfig(): ConfigFileService
    {
        return $this->config;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getContext(): ContextInterface|SessionContextInterface
    {
        return $this->context;
    }
}
