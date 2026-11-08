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

namespace SP\Domain\Plugin\UseCases;

use LogicException;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\UseCases\Dtos\TogglePluginCommand;
use SP\Domain\Plugin\UseCases\Dtos\TogglePluginResult;

/**
 * Enable or disable a plugin use case.
 *
 * @implements UseCase<TogglePluginCommand, TogglePluginResult>
 */
final readonly class TogglePluginUseCase implements UseCase
{
    public function __construct(
        private PluginManagerService $pluginManagerService,
        private EventEmitter $eventEmitter
    ) {
    }

    public function execute(Command $command): TogglePluginResult
    {
        if (!$command instanceof TogglePluginCommand) {
            throw new LogicException(sprintf('Expected command %s', TogglePluginCommand::class));
        }

        $this->pluginManagerService->toggleEnabled($command->id, $command->enabled);

        $this->eventEmitter->emit(
            $command->enabled ? 'edit.plugin.enable' : 'edit.plugin.disable',
            $this,
            EventMessage::build()->addDescription($command->enabled ? 'Plugin enabled' : 'Plugin disabled')
        );

        return new TogglePluginResult($command->id, $command->enabled);
    }
}
