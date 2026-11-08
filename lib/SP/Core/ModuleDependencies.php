<?php

declare(strict_types=1);
/**
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
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;

/**
 * Immutable aggregate of the dependencies shared by every module bootstrap.
 *
 * It is a parameter object, not a service locator: it only exposes the typed
 * ports that the module bases consume and provides no container access.
 */
final readonly class ModuleDependencies
{
    public function __construct(
        private ConfigFileService        $config,
        private Context                  $context,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getConfig(): ConfigFileService
    {
        return $this->config;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
